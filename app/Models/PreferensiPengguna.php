<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferensiPengguna extends Model
{
    protected $table = 'preferensi_pengguna';

    protected $fillable = [
        'pengguna_id',
        'kategori',
    ];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
