<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\FinanceService;
use App\Services\OrderService;
use App\Services\SettingsService;

/**
 * The finance chair's section. Everything here is read-only reporting except
 * the expense ledger, the budget and recording a refund.
 */
final class FinanceController extends AdminController
{
    private function currentPeriod(): array
    {
        return FinanceService::period(
            (string) $this->request->input('period', 'all'),
            (string) $this->request->input('from', ''),
            (string) $this->request->input('to', '')
        );
    }

    public function overview(): string
    {
        $period = $this->currentPeriod();

        return $this->render('admin.finance-overview', 'Finance overview', [
            'period'        => $period,
            'summary'       => FinanceService::summary($period),
            'income'        => FinanceService::incomeByCategory($period),
            'fees'          => FinanceService::fees($period),
            'daily'         => array_slice(FinanceService::dailyIncome($period, 30), 0, 14),
            'budget'        => FinanceService::budgetVsActual($period),
            'expenses'      => FinanceService::expensesByCategory($period),
            'upcoming'      => FinanceService::upcomingPayments(8),
            'donations'     => FinanceService::donationBreakdown($period),
            'discounts'     => FinanceService::discountCost($period),
            'vat'           => FinanceService::vat($period),
            'exceptions'    => FinanceService::reconciliationExceptions(),
            'stock'         => FinanceService::stockOnHand(),
        ]);
    }

    public function income(): string
    {
        $period = $this->currentPeriod();

        return $this->render('admin.finance-income', 'Income', [
            'period'        => $period,
            'summary'       => FinanceService::summary($period),
            'income'        => FinanceService::incomeByCategory($period),
            'products'      => FinanceService::productPerformance($period),
            'accommodation' => FinanceService::accommodationRevenue($period),
            'transport'     => FinanceService::transportRevenue($period),
            'donations'     => FinanceService::donationBreakdown($period),
            'discounts'     => FinanceService::discountCost($period),
            'daily'         => FinanceService::dailyIncome($period, 60),
            'vat'           => FinanceService::vat($period),
        ]);
    }

    /* --------------------------------------------------------- expenses */

    public function expenses(): string
    {
        $period = $this->currentPeriod();
        $status = (string) $this->request->input('status', '');

        $where  = ['e.incurred_on BETWEEN :from AND :to'];
        $params = ['from' => $period['from'], 'to' => $period['to']];

        if ($status !== '') {
            $where[]          = 'e.status = :status';
            $params['status'] = $status;
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM expenses e WHERE {$clause}",
            "SELECT e.*, c.name AS category_name, u.email AS created_by_email
               FROM expenses e
          LEFT JOIN expense_categories c ON c.id = e.category_id
          LEFT JOIN users u ON u.id = e.created_by
              WHERE {$clause}
           ORDER BY e.incurred_on DESC, e.id DESC",
            $params,
            50
        );

        return $this->render('admin.finance-expenses', 'Expenses', [
            'period'     => $period,
            'status'     => $status,
            'result'     => $result,
            'categories' => Database::select('SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY sort_order, name'),
            'totals'     => FinanceService::expenseTotals($period),
            'byCategory' => FinanceService::expensesByCategory($period),
            'upcoming'   => FinanceService::upcomingPayments(15),
        ]);
    }

    public function saveExpense(): never
    {
        $validator = Validator::make($this->request->all(), [
            'description' => 'required|max:255',
            'amount'      => 'required|numeric|gte:0',
            'incurred_on' => 'required|date',
            'status'      => 'required|in:planned,committed,invoiced,paid,cancelled',
        ], ['amount' => 'Amount', 'incurred_on' => 'Date incurred']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id     = $this->request->int('id', 0);
        $status = (string) $this->request->input('status');
        $paidOn = (string) $this->request->input('paid_on', '');

        $data = [
            'category_id'    => $this->request->int('category_id', 0) ?: null,
            'supplier'       => (string) $this->request->input('supplier', ''),
            'description'    => (string) $this->request->input('description'),
            'amount_cents'   => rands($this->request->input('amount')),
            'vat_cents'      => rands($this->request->input('vat', 0)),
            'incurred_on'    => date('Y-m-d', (int) strtotime((string) $this->request->input('incurred_on'))),
            'due_on'         => ($due = (string) $this->request->input('due_on', '')) !== '' ? date('Y-m-d', (int) strtotime($due)) : null,
            'paid_on'        => $paidOn !== '' ? date('Y-m-d', (int) strtotime($paidOn)) : ($status === 'paid' ? date('Y-m-d') : null),
            'status'         => $status,
            'payment_method' => (string) $this->request->input('payment_method', ''),
            'invoice_number' => (string) $this->request->input('invoice_number', ''),
            'notes'          => (string) $this->request->input('notes', ''),
        ];

        if ($id > 0) {
            Database::update('expenses', $data, 'id = :id', ['id' => $id]);
            $this->audit('updated an expense', 'expense', $id, ['amount' => $data['amount_cents']]);
            $this->flashSuccess('Expense saved.');
        } else {
            $data['reference']  = reference_code('EXP');
            $data['created_by'] = AuthService::id();

            $id = Database::insert('expenses', $data);
            $this->audit('recorded an expense', 'expense', $id, ['amount' => $data['amount_cents']]);
            $this->flashSuccess('Expense recorded.');
        }

        $this->back(url('/admin/finance/expenses'));
    }

    public function deleteExpense(string $id): never
    {
        $expense = Database::first('SELECT * FROM expenses WHERE id = ?', [(int) $id]);

        if ($expense === null) {
            $this->abort(404);
        }

        // Paid expenses are part of the record; cancel rather than erase.
        if ($expense['status'] === 'paid') {
            Database::update('expenses', ['status' => 'cancelled'], 'id = :id', ['id' => (int) $id]);
            $this->flashSuccess('That expense was already paid, so it was cancelled rather than deleted. The record stays in the ledger.');
        } else {
            Database::delete('expenses', 'id = ?', [(int) $id]);
            $this->flashSuccess('Expense deleted.');
        }

        $this->audit('removed an expense', 'expense', (int) $id);
        $this->back(url('/admin/finance/expenses'));
    }

    /* ----------------------------------------------------------- budget */

    public function budget(): string
    {
        $period = $this->currentPeriod();

        return $this->render('admin.finance-budget', 'Budget vs actual', [
            'period' => $period,
            'budget' => FinanceService::budgetVsActual($period),
            'lines'  => Database::select('SELECT * FROM budget_lines ORDER BY kind, sort_order, id'),
        ]);
    }

    public function saveBudgetLine(): never
    {
        $validator = Validator::make($this->request->all(), [
            'kind'     => 'required|in:income,expense',
            'category' => 'required|max:120',
            'budgeted' => 'required|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'kind'           => (string) $this->request->input('kind'),
            'category'       => (string) $this->request->input('category'),
            'description'    => (string) $this->request->input('description', ''),
            'budgeted_cents' => rands($this->request->input('budgeted')),
            'notes'          => (string) $this->request->input('notes', ''),
            'sort_order'     => $this->request->int('sort_order', 0),
        ];

        if ($id > 0) {
            Database::update('budget_lines', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Budget line saved.');
        } else {
            $id = Database::insert('budget_lines', $data);
            $this->flashSuccess('Budget line added.');
        }

        $this->audit('edited the budget', 'budget_line', $id);
        $this->back(url('/admin/finance/budget'));
    }

    public function deleteBudgetLine(string $id): never
    {
        Database::delete('budget_lines', 'id = ?', [(int) $id]);
        $this->audit('deleted a budget line', 'budget_line', (int) $id);
        $this->flashSuccess('Budget line removed.');
        $this->back(url('/admin/finance/budget'));
    }

    /* --------------------------------------------------------- refunds */

    public function refunds(): string
    {
        $period = $this->currentPeriod();

        return $this->render('admin.finance-refunds', 'Refunds', [
            'period'  => $period,
            'refunds' => FinanceService::refunds($period),
            'total'   => (int) Database::scalar(
                'SELECT COALESCE(SUM(amount_cents), 0) FROM refunds WHERE status = "completed"
                   AND COALESCE(refunded_on, DATE(created_at)) BETWEEN ? AND ?',
                [$period['from'], $period['to']]
            ),
        ]);
    }

    /** Record a refund against an order. Money moves in PayFast, not here. */
    public function recordRefund(string $orderId): never
    {
        $order = OrderService::find((int) $orderId);

        if ($order === null) {
            $this->abort(404);
        }

        // You cannot refund money that was never taken. The order screen only
        // offers this form on a paid order, but the form is not the guard: a
        // refund against an unpaid order is subtracted from net income while
        // its total was never counted as income, so the surplus the treasurer
        // reports would be understated by the whole amount.
        if (!in_array($order['status'], ['paid', 'refunded'], true)) {
            $this->flashError('That order has not been paid, so there is nothing to refund.');
            $this->back(url('/admin/orders/' . $order['id']));
        }

        $validator = Validator::make($this->request->all(), [
            'amount' => 'required|numeric|gt:0',
            'reason' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $amount          = rands($this->request->input('amount'));
        $alreadyRefunded = FinanceService::refundedTotal((int) $order['id']);
        $refundable      = (int) $order['total_cents'] - $alreadyRefunded;

        if ($amount > $refundable) {
            $this->flashError(sprintf(
                'That is more than is left to refund on this order. Paid %s, already refunded %s, so at most %s can be refunded.',
                money((int) $order['total_cents']),
                money($alreadyRefunded),
                money($refundable)
            ));
            $this->back();
        }

        $refundId = Database::insert('refunds', [
            'reference'          => reference_code('REF'),
            'order_id'           => (int) $order['id'],
            'payment_id'         => Database::scalar('SELECT id FROM payments WHERE order_id = ? AND status = "complete" ORDER BY id DESC LIMIT 1', [(int) $order['id']]),
            'amount_cents'       => $amount,
            'reason'             => (string) $this->request->input('reason'),
            'category'           => (string) ($this->request->input('category') ?: 'mixed'),
            'method'             => (string) ($this->request->input('method') ?: 'payfast'),
            'provider_reference' => (string) $this->request->input('provider_reference', ''),
            'status'             => 'completed',
            'refunded_on'        => ($on = (string) $this->request->input('refunded_on', '')) !== '' ? date('Y-m-d', (int) strtotime($on)) : date('Y-m-d'),
            'created_by'         => AuthService::id(),
        ]);

        // A full refund releases the beds and seats; a partial one does not,
        // because the rest of the booking still stands.
        $isFullRefund = ($alreadyRefunded + $amount) >= (int) $order['total_cents'];

        if ($isFullRefund && $this->request->bool('release_inventory')) {
            OrderService::markRefunded($order, 'Fully refunded: ' . $this->request->input('reason'));
            $this->flashSuccess('Refund recorded and the beds and seats have been released back to inventory.');
        } else {
            OrderService::log((int) $order['id'], 'refund_recorded', sprintf(
                'Refund of %s recorded by an administrator: %s',
                money($amount),
                (string) $this->request->input('reason')
            ));

            $this->flashSuccess($isFullRefund
                ? 'Refund recorded. Inventory was not released — release it from the order if the booking is cancelled too.'
                : 'Partial refund of ' . money($amount) . ' recorded.');
        }

        $this->audit('recorded a refund of ' . money($amount), 'order', (int) $order['id'], ['refund_id' => $refundId]);
        $this->back(url('/admin/orders/' . $order['id']));
    }

    /* -------------------------------------------------- reconciliation */

    public function reconciliation(): string
    {
        $period = $this->currentPeriod();

        // The payment list is paginated: a full convention runs to hundreds of
        // payments and the treasurer ticks them off a page at a time, or takes
        // the whole lot away in the CSV.
        $payments = $this->paginate(
            'SELECT COUNT(*) FROM payments p JOIN orders o ON o.id = p.order_id
              WHERE p.status = "complete" AND p.created_at BETWEEN :from AND :to',
            'SELECT p.id, p.created_at, p.provider_reference, p.amount_cents, p.fee_cents,
                    (p.amount_cents - p.fee_cents) AS net_cents,
                    o.id AS order_id, o.reference AS order_reference, o.email, o.status AS order_status
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE p.status = "complete" AND p.created_at BETWEEN :from AND :to
           ORDER BY p.created_at DESC',
            ['from' => $period['from'] . ' 00:00:00', 'to' => $period['to'] . ' 23:59:59'],
            50
        );

        return $this->render('admin.finance-reconciliation', 'Bank reconciliation', [
            'period'     => $period,
            'result'     => $payments,
            'rows'       => $payments['rows'],
            'rowTotals'  => FinanceService::reconciliationTotals($period),
            'exceptions' => FinanceService::reconciliationExceptions(),
            'fees'       => FinanceService::fees($period),
            'summary'    => FinanceService::summary($period),
            'entries'    => Database::select(
                'SELECT b.*, u.email AS created_by_email FROM bank_reconciliations b
              LEFT JOIN users u ON u.id = b.created_by
                  WHERE b.statement_date BETWEEN ? AND ?
               ORDER BY b.statement_date DESC, b.id DESC',
                [$period['from'], $period['to']]
            ),
        ]);
    }

    public function saveReconciliation(): never
    {
        $validator = Validator::make($this->request->all(), [
            'statement_date' => 'required|date',
            'amount'         => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'statement_date'      => date('Y-m-d', (int) strtotime((string) $this->request->input('statement_date'))),
            'description'         => (string) $this->request->input('description', ''),
            'amount_cents'        => rands($this->request->input('amount')),
            'matched_payment_ids' => (string) $this->request->input('matched_payment_ids', ''),
            'is_reconciled'       => $this->request->bool('is_reconciled') ? 1 : 0,
            'notes'               => (string) $this->request->input('notes', ''),
        ];

        if ($id > 0) {
            Database::update('bank_reconciliations', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Statement line updated.');
        } else {
            $data['created_by'] = AuthService::id();
            $id = Database::insert('bank_reconciliations', $data);
            $this->flashSuccess('Statement line recorded.');
        }

        $this->audit('updated the bank reconciliation', 'bank_reconciliation', $id);
        $this->back(url('/admin/finance/reconciliation'));
    }

    public function deleteReconciliation(string $id): never
    {
        Database::delete('bank_reconciliations', 'id = ?', [(int) $id]);
        $this->audit('deleted a reconciliation line', 'bank_reconciliation', (int) $id);
        $this->flashSuccess('Statement line removed.');
        $this->back(url('/admin/finance/reconciliation'));
    }
}
