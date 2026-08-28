<?php
declare(strict_types=1);

namespace App\Core;

class HttpException extends \RuntimeException
{
    public function __construct(private readonly int $status, string $message = '')
    {
        parent::__construct($message === '' ? self::defaultMessage($status) : $message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            400     => 'That request could not be understood.',
            401     => 'Please sign in to continue.',
            403     => 'You do not have permission to view this page.',
            404     => 'We could not find that page.',
            405     => 'That method is not allowed here.',
            419     => 'Your session expired. Please try again.',
            429     => 'Too many attempts. Please wait a moment.',
            default => 'Something went wrong on our side.',
        };
    }
}
