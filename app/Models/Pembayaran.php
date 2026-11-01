<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'metode',
        'id_transaksi',
        'tipe_pembayaran',
        'jumlah_bayar',
        'status_pembayaran',
        'dikonfirmasi_oleh',
        'tanggal_bayar',
        'midtrans_order_id',
        'snap_redirect_url',
        'snap_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_bayar' => 'decimal:2',
            'tanggal_bayar' => 'datetime',
            'snap_expires_at' => 'datetime',
            'snap_token',
        ];
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function pengonfirmasi()
    {
        return $this->belongsTo(Pengguna::class, 'dikonfirmasi_oleh');
    }
}