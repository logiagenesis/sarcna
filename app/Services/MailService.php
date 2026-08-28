<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Mailer;

/**
 * Every transactional email the site sends. Subjects are editable in the admin
 * (email_templates); the bodies render from app/Views/emails.
 */
final class MailService
{
    public static function adminAddress(): string
    {
        return (string) SettingsService::get('admin_notification_email', SettingsService::get('contact_email', Config::get('contact.email')));
    }

    private static function subject(string $key, string $default, array $replacements = []): string
    {
        $template = Database::first('SELECT subject FROM email_templates WHERE key_name = ? AND is_active = 1', [$key]);
        $subject  = $template === null ? $default : (string) $template['subject'];

        foreach ($replacements as $token => $value) {
            $subject = str_replace('{' . $token . '}', (string) $value, $subject);
        }

        return $subject;
    }

    /* ------------------------------------------------------------ accounts */

    public static function verifyEmail(array $user, string $token): bool
    {
        return Mailer::make()
            ->to((string) $user['email'], trim($user['first_name'] . ' ' . $user['last_name']))
            ->subject(self::subject('account_verification', 'Confirm your email for SARCNA 2027'))
            ->template('verify-email', [
                'user' => $user,
                'link' => url('/verify-email?token=' . $token),
            ])
            ->send();
    }

    public static function passwordReset(array $user, string $token): bool
    {
        return Mailer::make()
            ->to((string) $user['email'], trim($user['first_name'] . ' ' . $user['last_name']))
            ->subject(self::subject('password_reset', 'Reset your SARCNA 2027 password'))
            ->template('password-reset', [
                'user' => $user,
                'link' => url('/reset-password?token=' . $token),
            ])
            ->send();
    }

    public static function welcome(array $user): bool
    {
        return Mailer::make()
            ->to((string) $user['email'], trim($user['first_name'] . ' ' . $user['last_name']))
            ->subject(self::subject('welcome', 'Welcome to SARCNA 2027'))
            ->template('welcome', ['user' => $user])
            ->send();
    }

    /* -------------------------------------------------------------- orders */

    public static function orderCreated(array $order): bool
    {
        return Mailer::make()
            ->to((string) $order['email'], trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')))
            ->subject(self::subject('order_confirmation', 'Your SARCNA 2027 order {reference}', ['reference' => $order['reference']]))
            ->template('order-created', [
                'order' => $order,
                'items' => OrderService::items((int) $order['id']),
            ])
            ->send();
    }

    public static function orderPaid(array $order): bool
    {
        return Mailer::make()
            ->to((string) $order['email'], trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')))
            ->subject(self::subject('payment_received', 'Payment received — SARCNA 2027 order {reference}', ['reference' => $order['reference']]))
            ->template('order-paid', [
                'order'             => $order,
                'items'             => OrderService::items((int) $order['id']),
                'bookings'          => AccommodationService::bookingsForOrder((int) $order['id']),
                'transportBookings' => TransportService::bookingsForOrder((int) $order['id']),
            ])
            ->send();
    }

    public static function paymentFailed(array $order): bool
    {
        return Mailer::make()
            ->to((string) $order['email'])
            ->subject(self::subject('payment_failed', 'We could not process your payment — order {reference}', ['reference' => $order['reference']]))
            ->template('payment-failed', ['order' => $order])
            ->send();
    }

    /* ------------------------------------------------------------- forms */

    public static function serviceApplicationReceived(array $application): bool
    {
        Mailer::make()
            ->to(self::adminAddress())
            ->subject('New service application — ' . $application['name'])
            ->template('admin-service-application', ['application' => $application])
            ->send();

        return Mailer::make()
            ->to((string) $application['email'], (string) $application['name'])
            ->subject(self::subject('service_application', 'We received your SARCNA 2027 service application'))
            ->template('service-application', ['application' => $application])
            ->send();
    }

    public static function contactReceived(array $message): bool
    {
        Mailer::make()
            ->to(self::adminAddress())
            ->replyTo((string) $message['email'], (string) $message['name'])
            ->subject('Website enquiry: ' . $message['subject'])
            ->template('admin-contact', ['message' => $message])
            ->send();

        return Mailer::make()
            ->to((string) $message['email'], (string) $message['name'])
            ->subject(self::subject('contact_received', 'Thank you for contacting SARCNA 2027'))
            ->template('contact-received', ['message' => $message])
            ->send();
    }

    public static function donationReceived(array $donation): bool
    {
        if ($donation['email'] === null || $donation['email'] === '') {
            return false;
        }

        return Mailer::make()
            ->to((string) $donation['email'])
            ->subject(self::subject('donation_received', 'Thank you for supporting SARCNA 2027'))
            ->template('donation-received', ['donation' => $donation])
            ->send();
    }

    /* -------------------------------------------------------------- admin */

    public static function adminNewOrder(array $order): bool
    {
        return Mailer::make()
            ->to(self::adminAddress())
            ->subject('New paid order ' . $order['reference'] . ' — ' . money((int) $order['total_cents']))
            ->template('admin-new-order', [
                'order' => $order,
                'items' => OrderService::items((int) $order['id']),
            ])
            ->send();
    }

    public static function adminFailedPayment(array $order, string $reason): bool
    {
        return Mailer::make()
            ->to(self::adminAddress())
            ->subject('Failed payment on order ' . $order['reference'])
            ->template('admin-failed-payment', ['order' => $order, 'reason' => $reason])
            ->send();
    }

    public static function adminLowStock(array $products): bool
    {
        return Mailer::make()
            ->to(self::adminAddress())
            ->subject('Low stock warning — ' . count($products) . ' item(s)')
            ->template('admin-low-stock', ['products' => $products])
            ->send();
    }

    public static function adminAccommodationConflict(array $order, array $items): bool
    {
        return Mailer::make()
            ->to(self::adminAddress())
            ->subject('ACTION NEEDED: bed allocation failed on order ' . $order['reference'])
            ->template('admin-accommodation-conflict', ['order' => $order, 'items' => $items])
            ->send();
    }

    /** Generic message from the admin to an applicant or customer. */
    public static function custom(string $to, string $subject, string $body, string $name = ''): bool
    {
        return Mailer::make()
            ->to($to, $name)
            ->subject($subject)
            ->template('generic', ['body' => $body, 'name' => $name])
            ->send();
    }
}
