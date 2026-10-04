<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('palugada:about', function () {
    $this->info('Tenun Ikat Palugada Bandar - Sprint 1');
})->purpose('Menampilkan identitas project');
