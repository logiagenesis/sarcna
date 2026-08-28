<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Services\MailService;
use App\Services\SeoService;
use App\Services\SettingsService;

final class ContactController extends Controller
{
    public function index(): string
    {
        SeoService::breadcrumbs(['Contact' => '/contact']);

        return $this->page('pages.contact', [
            'title'       => 'Contact the Committee',
            'description' => 'Get in touch with the SARCNA 2027 Convention committee about registration, accommodation, transport, service or anything else.',
        ], [
            'emails' => array_filter([
                'General enquiries'   => (string) SettingsService::get('contact_email', ''),
                'Registration'        => (string) SettingsService::get('registration_email', ''),
                'Accommodation'       => (string) SettingsService::get('accommodation_email', ''),
                'Transport'           => (string) SettingsService::get('transport_email', ''),
            ]),
            'phone' => (string) SettingsService::get('contact_phone', ''),
        ]);
    }

    public function store(): never
    {
        if (trim((string) $this->request->input('website', '')) !== '') {
            $this->success('Thank you — your message has been sent.');
            $this->redirect(url('/contact'));
        }

        $validator = Validator::make($this->request->all(), [
            'name'    => 'required|max:160',
            'email'   => 'required|email|max:190',
            'phone'   => 'phone',
            'subject' => 'required|max:190',
            'message' => 'required|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $message = [
            'name'      => (string) $this->request->input('name'),
            'email'     => strtolower((string) $this->request->input('email')),
            'phone'     => (string) $this->request->input('phone', ''),
            'subject'   => (string) $this->request->input('subject'),
            'message'   => (string) $this->request->input('message'),
            'status'    => 'new',
            'source_ip' => $this->request->ip(),
        ];

        $id = Database::insert('contact_messages', $message);

        MailService::contactReceived(array_merge($message, ['id' => $id]));

        $this->success('Thank you — your message is with the committee and someone will reply soon.');
        $this->redirect(url('/contact'));
    }
}
