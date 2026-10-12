
<?php

return [

    // Konfigurasi Midtrans
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    ],

    // Konfigurasi Supabase Storage
    'supabase' => [
        'url' => env('SUPABASE_URL', 'https://imclxhmluevlpstymjjh.supabase.co'),
        'bucket' => env('SUPABASE_STORAGE_BUCKET', 'produk-palugada'),
        'secret_key' => env('SUPABASE_SECRET_KEY',''),
    ],

];
