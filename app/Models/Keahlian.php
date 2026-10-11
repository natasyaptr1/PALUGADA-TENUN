<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keahlian extends Model
{
    use HasFactory;

    protected $table = 'keahlian';

    protected $fillable = [
        'nama_keahlian',
        'keterangan',
    ];

    public function profilPenenun(): BelongsToMany
    {
        return $this->belongsToMany(ProfilPenenun::class, 'penenun_keahlian', 'keahlian_id', 'profil_penenun_id')
            ->withTimestamps();
    }
}
