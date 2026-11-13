<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception internal untuk menandai kegagalan sistem pada satu kurir
 * (timeout, 5xx, 401/403, koneksi terputus). Tidak ditampilkan ke pengguna.
 */
class OngkirSystemFailureException extends RuntimeException
{
}

