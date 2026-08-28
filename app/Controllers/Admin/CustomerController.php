<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\TransportService;

final class CustomerController extends AdminController
{
    public function index(): string
    {
        $search = trim((string) $this->request->input('q', ''));
        $filter = (string) $this->request->input('type', '');

        $where  = ['1 = 1'];
        $params = [];

        if ($search !== '') {
            $where[]          = '(u.email LIKE :search OR CONCAT(u.first_name, " ", u.last_name) LIKE :search OR u.phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($filter === 'admins') {
            $where[] = 'u.is_admin = 1';
        } elseif ($filter === 'customers') {
            $where[] = 'u.is_admin = 0';
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM users u WHERE {$clause}",
            "SELECT u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') AS paid_orders,
                    (SELECT COALESCE(SUM(o.total_cents), 0) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') AS spent
               FROM users u WHERE {$clause} ORDER BY u.created_at DESC",
            $params
        );

        return $this->render('admin.customers', 'Customers & admins', [
            'result' => $result,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function show(string $id): string
    {
        $user = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);

        if ($user === null) {
            $this->abort(404);
        }

        return $this->render('admin.customer-show', $user['first_name'] . ' ' . $user['last_name'], [
            'customer'  => $user,
            'roles'     => array_column(Database::select('SELECT role FROM user_roles WHERE user_id = ?', [(int) $user['id']]), 'role'),
            'orders'    => OrderService::forUser((int) $user['id']),
            'bookings'  => AccommodationService::bookingsForUser((int) $user['id']),
            'transport' => TransportService::bookingsForUser((int) $user['id']),
        ]);
    }

    public function updateRoles(string $id): never
    {
        $user = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);

        if ($user === null) {
            $this->abort(404);
        }

        $roles = array_values(array_intersect(
            $this->request->array('roles'),
            array_keys(AuthService::ROLE_PERMISSIONS)
        ));

        // Never let the last super admin remove their own access.
        if ((int) $user['id'] === auth_id() && !in_array('super_admin', $roles, true)) {
            $remaining = (int) Database::scalar(
                'SELECT COUNT(*) FROM user_roles WHERE role = "super_admin" AND user_id <> ?',
                [(int) $user['id']]
            );

            if ($remaining === 0) {
                $this->flashError('You are the only super admin. Give someone else the role before removing your own.');
                $this->back();
            }
        }

        Database::delete('user_roles', 'user_id = ?', [(int) $user['id']]);

        foreach ($roles as $role) {
            Database::insert('user_roles', ['user_id' => (int) $user['id'], 'role' => $role]);
        }

        Database::update('users', ['is_admin' => $roles === [] ? 0 : 1], 'id = :id', ['id' => (int) $user['id']]);

        $this->audit('changed admin roles', 'user', (int) $user['id'], ['roles' => $roles]);
        $this->flashSuccess('Roles updated for ' . $user['email'] . '.');
        $this->back(url('/admin/customers/' . $user['id']));
    }

    public function updateStatus(string $id): never
    {
        $user   = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);
        $status = (string) $this->request->input('status', 'active');

        if ($user === null) {
            $this->abort(404);
        }

        if ((int) $user['id'] === auth_id()) {
            $this->flashError('You cannot suspend your own account.');
            $this->back();
        }

        if (!in_array($status, ['active', 'suspended'], true)) {
            $this->back();
        }

        Database::update('users', ['status' => $status], 'id = :id', ['id' => (int) $user['id']]);

        $this->audit('set account status to ' . $status, 'user', (int) $user['id']);
        $this->flashSuccess($user['email'] . ' is now ' . $status . '.');
        $this->back(url('/admin/customers/' . $user['id']));
    }
}
