<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class OngkirException extends RuntimeException
{
    /**
     * Buat instance exception baru dengan pesan ramah untuk pengguna.
     */
    public function __construct(
        string $message = 'Terjadi kesalahan pada layanan ongkos kirim.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

