<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi API Ongkos Kirim (RajaOngkir / Komerce)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini mengatur parameter integrasi untuk kalkulasi ongkos kirim
    | otomatis via API kurir dan pencarian wilayah tujuan.
    |
    */

    'api_key' => env('ONGKIR_API_KEY') ?: null,

    'base_url' => env('ONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),

    'asal_id' => env('ONGKIR_ASAL_ID') ?: null,

    'kurir' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('ONGKIR_KURIR', 'jne,jnt,sicepat'))),
        fn (string $item): bool => $item !== ''
    )),

    'berat_default_gram' => (int) env('ONGKIR_BERAT_DEFAULT_GRAM', 500),

    'cache_ongkir_menit' => (int) env('ONGKIR_CACHE_ONGKIR_MENIT', 1440),

    'cache_tujuan_menit' => (int) env('ONGKIR_CACHE_TUJUAN_MENIT', 10080),

    'timeout_detik' => (int) env('ONGKIR_TIMEOUT_DETIK', 10),

    'label_ambil_di_tempat' => env('ONGKIR_LABEL_AMBIL', 'Ambil di tempat (Palugada Bandar)'),
];

