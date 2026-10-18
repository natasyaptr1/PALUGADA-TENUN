<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TargetProduksi extends Model
{
    protected $table = 'target_produksi';

    protected $fillable = [
        'produk_id',
        'penenun_id',
        'jumlah_target',
        'tanggal_mulai',
        'tanggal_deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_deadline' => 'date',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function penenun(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'penenun_id');
    }

    public function hasilProduksi(): HasMany
    {
        return $this->hasMany(HasilProduksi::class, 'target_produksi_id');
    }

    public function totalSelesai(): int
    {
        if (array_key_exists('hasil_produksi_sum_jumlah_selesai', $this->attributes)) {
            return (int) ($this->attributes['hasil_produksi_sum_jumlah_selesai'] ?? 0);
        }

        if ($this->relationLoaded('hasilProduksi')) {
            return (int) $this->hasilProduksi->sum('jumlah_selesai');
        }

        return (int) $this->hasilProduksi()->sum('jumlah_selesai');
    }

    public function getTotalSelesaiAttribute(): int
    {
        return $this->totalSelesai();
    }

    public function sisaTarget(): int
    {
        return max(0, (int) $this->jumlah_target - $this->totalSelesai());
    }

    public function getSisaTargetAttribute(): int
    {
        return $this->sisaTarget();
    }

    public function persenProgres(): int
    {
        $target = (int) $this->jumlah_target;
        if ($target <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->totalSelesai() / $target) * 100));
    }

    public function getPersenProgresAttribute(): int
    {
        return $this->persenProgres();
    }

    public function sedangTerlambat(): bool
    {
        if ($this->status !== 'berjalan' || empty($this->tanggal_deadline)) {
            return false;
        }

        $deadline = $this->tanggal_deadline->copy()->timezone('Asia/Jakarta')->startOfDay();
        $today = Carbon::today('Asia/Jakarta');

        return $deadline->lt($today);
    }

    public function getSedangTerlambatAttribute(): bool
    {
        return $this->sedangTerlambat();
    }

    public function hariTerlambat(): int
    {
        if (!$this->sedangTerlambat()) {
            return 0;
        }

        $deadline = $this->tanggal_deadline->copy()->timezone('Asia/Jakarta')->startOfDay();
        $today = Carbon::today('Asia/Jakarta');

        return (int) $deadline->diffInDays($today);
    }

    public function getHariTerlambatAttribute(): int
    {
        return $this->hariTerlambat();
    }
}
