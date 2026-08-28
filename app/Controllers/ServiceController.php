<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\SeoService;

final class ServiceController extends Controller
{
    public const SERVICE_AREAS = [
        'Registration', 'Hospitality', 'Merchandise', 'Transport', 'Accommodation',
        'Programme', 'Entertainment', 'Decor', 'Tea/Coffee', 'Security/Stewarding',
        'Clean-up', 'General Service',
    ];

    public function index(): string
    {
        SeoService::breadcrumbs(['Service' => '/service']);

        return $this->page('pages.service', [
            'title'       => 'Service & Volunteer Applications',
            'description' => 'Put your hand up to do service at the SARCNA 2027 Convention — registration, hospitality, transport, merchandise, stewarding and more.',
            'image'       => '/assets/img/conference/fellowship-lawn.jpg',
        ], ['areas' => self::SERVICE_AREAS]);
    }

    public function store(): never
    {
        // Honeypot: real people never fill this in.
        if (trim((string) $this->request->input('website', '')) !== '') {
            $this->flashSuccess('Thank you — your application has been received.');
            $this->redirect(url('/service'));
        }

        $areas = array_values(array_intersect($this->request->array('service_areas'), self::SERVICE_AREAS));

        $validator = Validator::make(array_merge($this->request->all(), ['service_areas' => $areas]), [
            'name'          => 'required|max:160',
            'email'         => 'required|email|max:190',
            'phone'         => 'required|phone',
            'region'        => 'max:120',
            'home_group'    => 'max:120',
            'clean_time'    => 'max:60',
            'service_areas' => 'required',
            'availability'  => 'max:255',
            'skills'        => 'max:500',
            'consent'       => 'required|accepted',
        ], [
            'service_areas' => 'At least one service area',
            'consent'       => 'the consent checkbox',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $application = [
            'reference'     => reference_code('SVC'),
            'user_id'       => AuthService::id(),
            'name'          => (string) $this->request->input('name'),
            'email'         => strtolower((string) $this->request->input('email')),
            'phone'         => (string) $this->request->input('phone'),
            'region'        => (string) $this->request->input('region', ''),
            'home_group'    => (string) $this->request->input('home_group', ''),
            'clean_time'    => (string) $this->request->input('clean_time', ''),
            'service_areas' => implode(', ', $areas),
            'availability'  => (string) $this->request->input('availability', ''),
            'skills'        => (string) $this->request->input('skills', ''),
            'notes'         => (string) $this->request->input('notes', ''),
            'status'        => 'new',
            'consent_at'    => date('Y-m-d H:i:s'),
        ];

        $id = Database::insert('service_applications', $application);

        MailService::serviceApplicationReceived(array_merge($application, ['id' => $id]));

        $this->flashSuccess('Thank you. Your application reference is ' . $application['reference'] . ' — the service co-ordinator will be in touch.');
        $this->redirect(url('/service'));
    }
}
