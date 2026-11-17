<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanPenjualanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $data) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Tanggal Pesanan', 'Kode Pesanan', 'Penerima', 'Jumlah Item', 'Subtotal Produk', 'Ongkir', 'Total Dibayar', 'Metode Pembayaran', 'Status Pembayaran', 'Status Pesanan'];
    }

    public function map($pesanan): array
    {
        $pembayaran = $pesanan->pembayaran->first();

        return [
            $pesanan->tanggal_pesanan?->format('d/m/Y H:i') ?? '-',
            $pesanan->kode_pesanan,
            $pesanan->nama_penerima,
            (int) $pesanan->detail->sum('jumlah'),
            (float) ($pesanan->subtotal ?? $pesanan->total),
            (float) ($pesanan->ongkir ?? 0),
            (float) $pesanan->total,
            strtoupper($pembayaran?->metode ?? '-'),
            ucfirst($pembayaran?->status_pembayaran ?? 'belum ada'),
            ucfirst(str_replace('_', ' ', $pesanan->status_pesanan)),
        ];
    }
}
