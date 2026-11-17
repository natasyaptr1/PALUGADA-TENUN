<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanProduksiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $data) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Produk', 'Kode Produk', 'Penenun', 'Tanggal Mulai', 'Target Selesai', 'Selesai Aktual', 'Target Unit', 'Hasil Unit', 'Sisa Unit', 'Status'];
    }

    public function map($item): array
    {
        $hasil = (int) ($item->total_selesai ?? 0);

        return [
            $item->produk?->nama_produk ?? '-',
            $item->produk?->kode_produk ?? '-',
            $item->penenun?->nama ?? '-',
            $item->tanggal_mulai?->format('d/m/Y') ?? '-',
            $item->tanggal_deadline?->format('d/m/Y') ?? '-',
            $item->status === 'selesai' && $item->tanggal_selesai_aktual
                ? Carbon::parse($item->tanggal_selesai_aktual)->format('d/m/Y')
                : '-',
            (int) $item->jumlah_target,
            $hasil,
            max(0, (int) $item->jumlah_target - $hasil),
            ucfirst($item->status),
        ];
    }
}
