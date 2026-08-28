<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;

final class MessageController extends AdminController
{
    public function index(): string
    {
        $status = (string) $this->request->input('status', '');

        $where  = $status === '' ? '1 = 1' : 'status = :status';
        $params = $status === '' ? [] : ['status' => $status];

        $result = $this->paginate(
            "SELECT COUNT(*) FROM contact_messages WHERE {$where}",
            "SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC",
            $params,
            40
        );

        return $this->render('admin.messages', 'Contact messages', [
            'result' => $result,
            'status' => $status,
            'unread' => (int) Database::scalar('SELECT COUNT(*) FROM contact_messages WHERE status = "new"'),
        ]);
    }

    public function update(string $id): never
    {
        $message = Database::first('SELECT * FROM contact_messages WHERE id = ?', [(int) $id]);

        if ($message === null) {
            $this->abort(404);
        }

        $status = (string) $this->request->input('status', 'read');

        if (!in_array($status, ['new', 'read', 'replied', 'archived'], true)) {
            $status = 'read';
        }

        Database::update('contact_messages', [
            'status'      => $status,
            'admin_notes' => (string) $this->request->input('admin_notes', (string) $message['admin_notes']),
        ], 'id = :id', ['id' => (int) $message['id']]);

        $this->audit('updated a contact message to ' . $status, 'contact_message', (int) $message['id']);
        $this->flashSuccess('Message updated.');
        $this->back(url('/admin/messages'));
    }
}
