<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Controllers\ServiceController;
use App\Services\MailService;

final class ApplicationController extends AdminController
{
    public function index(): string
    {
        $status = (string) $this->request->input('status', '');
        $area   = (string) $this->request->input('area', '');

        $where  = ['1 = 1'];
        $params = [];

        if ($status !== '') {
            $where[]          = 'status = :status';
            $params['status'] = $status;
        }

        if ($area !== '') {
            $where[]        = 'service_areas LIKE :area';
            $params['area'] = '%' . $area . '%';
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM service_applications WHERE {$clause}",
            "SELECT * FROM service_applications WHERE {$clause} ORDER BY created_at DESC",
            $params,
            40
        );

        return $this->render('admin.applications', 'Service applications', [
            'result'  => $result,
            'status'  => $status,
            'area'    => $area,
            'areas'   => ServiceController::SERVICE_AREAS,
            'counts'  => Database::select('SELECT status, COUNT(*) AS total FROM service_applications GROUP BY status'),
        ]);
    }

    public function show(string $id): string
    {
        $application = Database::first('SELECT * FROM service_applications WHERE id = ?', [(int) $id]);

        if ($application === null) {
            $this->abort(404);
        }

        return $this->render('admin.application-show', 'Application ' . $application['reference'], [
            'application' => $application,
        ]);
    }

    public function update(string $id): never
    {
        $application = Database::first('SELECT * FROM service_applications WHERE id = ?', [(int) $id]);

        if ($application === null) {
            $this->abort(404);
        }

        $status = (string) $this->request->input('status', $application['status']);

        if (!in_array($status, ['new', 'reviewing', 'accepted', 'waitlisted', 'declined'], true)) {
            $status = $application['status'];
        }

        Database::update('service_applications', [
            'status'      => $status,
            'admin_notes' => (string) $this->request->input('admin_notes', ''),
        ], 'id = :id', ['id' => (int) $application['id']]);

        $this->audit('updated a service application to ' . $status, 'service_application', (int) $application['id']);
        $this->flashSuccess('Application updated.');
        $this->back(url('/admin/applications/' . $application['id']));
    }

    public function email(string $id): never
    {
        $application = Database::first('SELECT * FROM service_applications WHERE id = ?', [(int) $id]);

        if ($application === null) {
            $this->abort(404);
        }

        $subject = trim((string) $this->request->input('subject', ''));
        $body    = trim((string) $this->request->input('body', ''));

        if ($subject === '' || $body === '') {
            $this->flashError('Both a subject and a message are needed.');
            $this->back();
        }

        $sent = MailService::custom((string) $application['email'], $subject, $body, (string) $application['name']);

        $this->audit('emailed a service applicant', 'service_application', (int) $application['id'], ['subject' => $subject]);

        if ($sent) {
            $this->flashSuccess('Email sent to ' . $application['email'] . '.');
        } else {
            $this->flashError('The email could not be sent. Check Settings → Diagnostics.');
        }

        $this->back(url('/admin/applications/' . $application['id']));
    }
}
