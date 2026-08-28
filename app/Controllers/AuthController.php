<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\MailService;
use App\Services\RateLimiter;
use App\Services\SeoService;

final class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 6;
    private const LOCKOUT_SECONDS    = 900;

    /* --------------------------------------------------------------- login */

    public function showLogin(): string
    {
        SeoService::set(['robots' => 'noindex,follow']);

        return $this->page('pages.auth-login', [
            'title'       => 'Sign in',
            'description' => 'Sign in to your SARCNA 2027 Convention account to see your orders, accommodation and transport bookings.',
        ], []);
    }

    public function login(): never
    {
        $email = strtolower(trim((string) $this->request->input('email', '')));
        $key   = 'login:' . $email . ':' . $this->request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS, self::LOCKOUT_SECONDS)) {
            $minutes = max(1, (int) ceil(RateLimiter::secondsRemaining($key) / 60));
            $this->flashError('Too many sign-in attempts. Please try again in ' . $minutes . ' minute(s), or reset your password.');
            $this->redirect(url('/login'));
        }

        $validator = Validator::make($this->request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $user = AuthService::attempt($email, (string) $this->request->input('password', ''));

        if ($user === null) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            $this->flashError('That email address and password do not match our records.');
            Session::flashOld(['email' => $email]);
            $this->redirect(url('/login'));
        }

        RateLimiter::clear($key);
        Csrf::rotate();
        CartService::attachToUser((int) $user['id']);

        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        $this->flashSuccess('Welcome back, ' . $user['first_name'] . '.');

        if (is_string($intended) && $intended !== '' && !str_starts_with($intended, '/login')) {
            $this->redirect(url($intended));
        }

        $this->redirect(url(AuthService::isAdmin() ? '/admin' : '/account'));
    }

    public function logout(): never
    {
        AuthService::logout();

        $this->flashSuccess('You have been signed out.');
        $this->redirect(url('/'));
    }

    /* ------------------------------------------------------------ register */

    public function showRegister(): string
    {
        SeoService::set(['robots' => 'noindex,follow']);

        return $this->page('pages.auth-register', [
            'title'       => 'Create an account',
            'description' => 'Create a SARCNA 2027 Convention account to register, book a bed and reserve a shuttle seat.',
        ], []);
    }

    public function register(): never
    {
        $validator = Validator::make($this->request->all(), [
            'first_name' => 'required|max:80',
            'last_name'  => 'required|max:80',
            'email'      => 'required|email|max:190|unique:users,email',
            'phone'      => 'required|phone',
            'password'   => 'required|password|confirmed',
            'terms'      => 'required|accepted',
        ], [
            'terms'    => 'the terms and privacy policy',
            'password' => 'Password',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $userId = Database::insert('users', [
            'first_name'       => (string) $this->request->input('first_name'),
            'last_name'        => (string) $this->request->input('last_name'),
            'email'            => strtolower((string) $this->request->input('email')),
            'phone'            => (string) $this->request->input('phone'),
            'password_hash'    => AuthService::hash((string) $this->request->input('password')),
            'home_group'       => (string) $this->request->input('home_group', ''),
            'region'           => (string) $this->request->input('region', ''),
            'marketing_opt_in' => $this->request->bool('marketing_opt_in') ? 1 : 0,
        ]);

        $user = Database::first('SELECT * FROM users WHERE id = ?', [$userId]);

        $this->sendVerificationEmail($user);
        MailService::welcome($user);

        AuthService::login($user);
        CartService::attachToUser($userId);

        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        $this->flashSuccess('Welcome to SARCNA 2027. Check your email to confirm your address.');
        $this->redirect(url(is_string($intended) && $intended !== '' ? $intended : '/account'));
    }

    /* ------------------------------------------------------ email verify */

    public function verifyEmail(): never
    {
        $token = (string) $this->request->input('token', '');

        if ($token === '') {
            $this->flashError('That verification link is not valid.');
            $this->redirect(url('/account'));
        }

        $record = Database::first(
            'SELECT * FROM email_verifications WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            [hash('sha256', $token)]
        );

        if ($record === null) {
            $this->flashError('That verification link has expired. Please request a new one.');
            $this->redirect(url('/account'));
        }

        Database::update('users', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $record['user_id']]);
        Database::update('email_verifications', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $record['id']]);

        AuthService::refresh();

        $this->flashSuccess('Thank you — your email address is confirmed.');
        $this->redirect(url('/account'));
    }

    public function resendVerification(): never
    {
        $user = AuthService::user();

        if ($user === null) {
            $this->redirect(url('/login'));
        }

        if ($user['email_verified_at'] !== null) {
            $this->flashSuccess('Your email address is already confirmed.');
            $this->redirect(url('/account'));
        }

        $this->sendVerificationEmail($user);

        $this->flashSuccess('We have sent a new confirmation link to ' . $user['email'] . '.');
        $this->redirect(url('/account'));
    }

    private function sendVerificationEmail(array $user): void
    {
        $token = bin2hex(random_bytes(32));

        Database::insert('email_verifications', [
            'user_id'    => (int) $user['id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + (48 * 3600)),
        ]);

        MailService::verifyEmail($user, $token);
    }

    /* --------------------------------------------------- password reset */

    public function showForgotPassword(): string
    {
        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.auth-forgot', [
            'title'       => 'Forgot your password',
            'description' => 'Reset the password on your SARCNA 2027 Convention account.',
        ], []);
    }

    public function sendResetLink(): never
    {
        $email = strtolower(trim((string) $this->request->input('email', '')));

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $user = Database::first('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));

            Database::insert('password_resets', [
                'user_id'    => (int) $user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);

            MailService::passwordReset($user, $token);
        }

        // The same answer either way, so the form cannot be used to discover
        // which email addresses have accounts.
        $this->flashSuccess('If that email address has an account, a reset link is on its way.');
        $this->redirect(url('/forgot-password'));
    }

    public function showResetPassword(): string
    {
        SeoService::set(['robots' => 'noindex,nofollow']);

        return $this->page('pages.auth-reset', [
            'title'       => 'Choose a new password',
            'description' => 'Set a new password for your SARCNA 2027 Convention account.',
        ], ['token' => (string) $this->request->input('token', '')]);
    }

    public function resetPassword(): never
    {
        $validator = Validator::make($this->request->all(), [
            'token'    => 'required',
            'password' => 'required|password|confirmed',
        ], ['password' => 'Password']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $token  = (string) $this->request->input('token');
        $record = Database::first(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            [hash('sha256', $token)]
        );

        if ($record === null) {
            $this->flashError('That reset link has expired or has already been used. Please request a new one.');
            $this->redirect(url('/forgot-password'));
        }

        Database::update(
            'users',
            ['password_hash' => AuthService::hash((string) $this->request->input('password'))],
            'id = :id',
            ['id' => (int) $record['user_id']]
        );

        Database::update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $record['id']]);

        // Any other outstanding reset links for this account are now void.
        Database::update(
            'password_resets',
            ['used_at' => date('Y-m-d H:i:s')],
            'user_id = :user AND used_at IS NULL',
            ['user' => (int) $record['user_id']]
        );

        $this->flashSuccess('Your password has been changed. Please sign in.');
        $this->redirect(url('/login'));
    }
}
