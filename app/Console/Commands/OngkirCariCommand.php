<?php

namespace App\Console\Commands;

use App\Exceptions\OngkirException;
use App\Services\OngkirService;
use Illuminate\Console\Command;

class OngkirCariCommand extends Command
{
    /**
     * Nama dan signature perintah artisan.
     *
     * @var string
     */
    protected $signature = 'ongkir:cari {kata : Kata kunci pencarian tujuan pengiriman}';

    /**
     * Deskripsi perintah artisan.
     *
     * @var string
     */
    protected $description = 'Cari ID dan label wilayah tujuan pengiriman via API ongkir';

    /**
     * Eksekusi perintah artisan.
     */
    public function handle(OngkirService $ongkirService): int
    {
        $kata = (string) $this->argument('kata');

        try {
            $hasil = $ongkirService->cariTujuan($kata);

            if (empty($hasil)) {
                $this->info("Tidak ada hasil pencarian tujuan untuk kata kunci: \"{$kata}\"");
                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Label'],
                array_map(fn (array $item): array => [
                    $item['id'],
                    $item['label'],
                ], $hasil)
            );

            return self::SUCCESS;
        } catch (OngkirException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

