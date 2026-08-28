<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Services\AccommodationService;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\SeoService;
use App\Services\TransportService;

final class AccountController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        SeoService::set(['robots' => 'noindex,nofollow']);
    }

    public function index(): string
    {
        $user   = AuthService::user();
        $orders = OrderService::forUser((int) $user['id']);

        return $this->page('pages.account-dashboard', [
            'title'       => 'My account',
            'description' => 'Your SARCNA 2027 Convention bookings.',
        ], [
            'user'      => $user,
            'orders'    => array_slice($orders, 0, 5),
            'bookings'  => AccommodationService::bookingsForUser((int) $user['id']),
            'transport' => TransportService::bookingsForUser((int) $user['id']),
            'spent'     => (int) Database::scalar('SELECT COALESCE(SUM(total_cents), 0) FROM orders WHERE user_id = ? AND status = "paid"', [(int) $user['id']]),
            'showVerify' => $user['email_verified_at'] === null,
        ]);
    }

    public function orders(): string
    {
        return $this->page('pages.account-orders', [
            'title' => 'Order history',
        ], ['orders' => OrderService::forUser(auth_id())]);
    }

    public function order(string $reference): string
    {
        $order = $this->findOwnOrder($reference);

        return $this->page('pages.account-order', [
            'title' => 'Order ' . $order['reference'],
        ], [
            'order'             => $order,
            'items'             => OrderService::items((int) $order['id']),
            'bookings'          => AccommodationService::bookingsForOrder((int) $order['id']),
            'transportBookings' => TransportService::bookingsForOrder((int) $order['id']),
        ]);
    }

    public function invoice(string $reference): string
    {
        $order = $this->findOwnOrder($reference);

        return $this->view('pages.account-invoice', [
            'order'             => $order,
            'items'             => OrderService::items((int) $order['id']),
            'bookings'          => AccommodationService::bookingsForOrder((int) $order['id']),
            'transportBookings' => TransportService::bookingsForOrder((int) $order['id']),
        ]);
    }

    public function bookings(): string
    {
        return $this->page('pages.account-bookings', [
            'title' => 'Accommodation bookings',
        ], ['bookings' => AccommodationService::bookingsForUser(auth_id())]);
    }

    public function transport(): string
    {
        return $this->page('pages.account-transport', [
            'title' => 'Transport bookings',
        ], ['bookings' => TransportService::bookingsForUser(auth_id())]);
    }

    public function profile(): string
    {
        return $this->page('pages.account-profile', [
            'title' => 'My profile',
        ], ['user' => AuthService::user()]);
    }

    public function updateProfile(): never
    {
        $user = AuthService::user();

        $validator = Validator::make($this->request->all(), [
            'first_name' => 'required|max:80',
            'last_name'  => 'required|max:80',
            'email'      => 'required|email|max:190|unique:users,email,' . (int) $user['id'],
            'phone'      => 'required|phone',
            'home_group' => 'max:120',
            'region'     => 'max:120',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $email        = strtolower((string) $this->request->input('email'));
        $emailChanged = $email !== $user['email'];

        Database::update('users', [
            'first_name'          => (string) $this->request->input('first_name'),
            'last_name'           => (string) $this->request->input('last_name'),
            'email'               => $email,
            'phone'               => (string) $this->request->input('phone'),
            'home_group'          => (string) $this->request->input('home_group', ''),
            'region'              => (string) $this->request->input('region', ''),
            'dietary_notes'       => (string) $this->request->input('dietary_notes', ''),
            'accessibility_notes' => (string) $this->request->input('accessibility_notes', ''),
            'marketing_opt_in'    => $this->request->bool('marketing_opt_in') ? 1 : 0,
            'email_verified_at'   => $emailChanged ? null : $user['email_verified_at'],
        ], 'id = :id', ['id' => (int) $user['id']]);

        AuthService::refresh();

        $this->flashSuccess($emailChanged
            ? 'Profile updated. Please confirm your new email address.'
            : 'Your profile has been updated.');

        $this->redirect(url('/account/profile'));
    }

    public function updatePassword(): never
    {
        $user = AuthService::user();

        $validator = Validator::make($this->request->all(), [
            'current_password' => 'required',
            'password'         => 'required|password|confirmed',
        ], ['password' => 'New password']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        if (!password_verify((string) $this->request->input('current_password'), (string) $user['password_hash'])) {
            $this->flashError('Your current password is not correct.');
            $this->redirect(url('/account/profile'));
        }

        Database::update(
            'users',
            ['password_hash' => AuthService::hash((string) $this->request->input('password'))],
            'id = :id',
            ['id' => (int) $user['id']]
        );

        $this->flashSuccess('Your password has been changed.');
        $this->redirect(url('/account/profile'));
    }

    private function findOwnOrder(string $reference): array
    {
        $order = OrderService::findByReference($reference);

        if ($order === null) {
            $this->abort(404);
        }

        if ((int) $order['user_id'] !== auth_id() && !AuthService::isAdmin()) {
            $this->abort(403, 'That order belongs to a different account.');
        }

        return $order;
    }
}
