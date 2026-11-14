<?php

namespace App\Enums;

enum MetodePengiriman: string
{
    case AmbilDiTempat = 'ambil_di_tempat';
    case Ekspedisi = 'ekspedisi';

    /**
     * Dapatkan label ramah pengguna untuk metode pengiriman.
     */
    public function label(): string
    {
        return match ($this) {
            self::AmbilDiTempat => 'Ambil di Tempat',
            self::Ekspedisi => 'Ekspedisi',
        };
    }
}

