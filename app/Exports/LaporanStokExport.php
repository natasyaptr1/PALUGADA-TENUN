<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanStokExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $data) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Tanggal Mutasi', 'Kode Produk', 'Produk', 'Jenis Mutasi', 'Jumlah', 'Sumber', 'Referensi', 'Catatan'];
    }

    public function map($item): array
    {
        $referensi = $item->detailPesanan?->pesanan?->kode_pesanan
            ?? ($item->hasilProduksi?->targetProduksi ? 'Target #' . $item->hasilProduksi->targetProduksi->id : '-');

        return [
            $item->tanggal_mutasi?->format('d/m/Y H:i') ?? '-',
            $item->stok?->produk?->kode_produk ?? '-',
            $item->stok?->produk?->nama_produk ?? '-',
            ucfirst($item->jenis_mutasi),
            (int) $item->jumlah,
            ucfirst(str_replace('_', ' ', $item->sumber)),
            $referensi,
            $item->catatan ?: '-',
        ];
    }
}
