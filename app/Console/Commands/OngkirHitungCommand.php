<?php

namespace App\Console\Commands;

use App\Exceptions\OngkirException;
use App\Services\OngkirService;
use Illuminate\Console\Command;

class OngkirHitungCommand extends Command
{
    /**
     * Nama dan signature perintah artisan.
     *
     * @var string
     */
    protected $signature = 'ongkir:hitung {tujuan_id : ID wilayah tujuan pengiriman} {berat_gram : Berat paket dalam satuan gram}';

    /**
     * Deskripsi perintah artisan.
     *
     * @var string
     */
    protected $description = 'Hitung opsi dan tarif ongkir ke tujuan pengiriman via API kurir';

    /**
     * Eksekusi perintah artisan.
     */
    public function handle(OngkirService $ongkirService): int
    {
        $tujuanId = (string) $this->argument('tujuan_id');
        $beratGram = (int) $this->argument('berat_gram');

        try {
            $opsi = $ongkirService->hitung($tujuanId, $beratGram);

            if (empty($opsi)) {
                $this->info("Tidak ada opsi kurir yang tersedia untuk tujuan ID: {$tujuanId}");
                return self::SUCCESS;
            }

            $this->table(
                ['Kurir', 'Layanan', 'Biaya', 'Estimasi'],
                array_map(fn (array $item): array => [
                    $item['kurir_nama'],
                    $item['layanan'],
                    'Rp ' . number_format($item['biaya'], 0, ',', '.'),
                    $item['estimasi'],
                ], $opsi)
            );

            return self::SUCCESS;
        } catch (OngkirException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

