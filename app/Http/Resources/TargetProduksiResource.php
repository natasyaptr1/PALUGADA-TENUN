<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetProduksiResource extends JsonResource
{
    private bool $sertakanRiwayat = false;

    public function denganRiwayat(bool $status = true): self
    {
        $this->sertakanRiwayat = $status;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $produk = $this->produk;
        $fotoUrl = null;

        if ($produk) {
            $semuaFoto = $produk->semuaFoto();
            if (!empty($semuaFoto[0])) {
                $fotoUrl = asset('storage/' . $semuaFoto[0]);
            }
        }

        $item = [
            'id' => $this->id,
            'produk' => $produk ? [
                'id' => $produk->id,
                'kode_produk' => $produk->kode_produk,
                'nama_produk' => $produk->nama_produk,
                'jenis' => $produk->jenis,
                'motif' => $produk->motif,
                'warna' => $produk->warna,
                'ukuran' => $produk->ukuran,
                'material' => $produk->material,
                'foto_url' => $fotoUrl,
            ] : null,
            'jumlah_target' => (int) $this->jumlah_target,
            'total_selesai' => (int) $this->totalSelesai(),
            'sisa_target' => (int) $this->sisaTarget(),
            'persen_progres' => (int) $this->persenProgres(),
            'tanggal_mulai' => $this->tanggal_mulai ? $this->tanggal_mulai->format('Y-m-d') : null,
            'tanggal_deadline' => $this->tanggal_deadline ? $this->tanggal_deadline->format('Y-m-d') : null,
            'status' => $this->status,
            'terlambat' => (bool) $this->sedangTerlambat(),
            'hari_terlambat' => (int) $this->hariTerlambat(),
            'dibuat_pada' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];

        if ($this->sertakanRiwayat) {
            $item['riwayat_hasil'] = $this->hasilProduksi()
                ->latest('tanggal_input')
                ->latest('id')
                ->take(50)
                ->get()
                ->map(fn ($hasil) => [
                    'id' => $hasil->id,
                    'jumlah_selesai' => (int) $hasil->jumlah_selesai,
                    'tanggal_input' => $hasil->tanggal_input ? $hasil->tanggal_input->toIso8601String() : null,
                    'catatan' => $hasil->catatan,
                ])
                ->all();
        }

        return $item;
    }
}
