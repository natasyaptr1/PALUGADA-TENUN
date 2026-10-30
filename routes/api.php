<?php
use App\Http\Controllers\Api\V1\AutentikasiController;
use App\Http\Controllers\Api\V1\PenenunController;
use App\Http\Controllers\Api\V1\ProdukController;
use App\Http\Controllers\Api\V1\ProduksiPenenunController;
use App\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function(){
    Route::get('/status',fn()=>response()->json(['status'=>'ok','layanan'=>'API Palugada v1']));
    Route::post('/autentikasi/daftar',[AutentikasiController::class,'daftar']);
    Route::post('/autentikasi/masuk',[AutentikasiController::class,'masuk']);
    Route::get('/produk',[ProdukController::class,'index']);
    Route::get('/produk/{produk}',[ProdukController::class,'show']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/autentikasi/saya',[AutentikasiController::class,'saya']);
        Route::post('/autentikasi/keluar',[AutentikasiController::class,'keluar']);
        Route::middleware('peran:penenun')->prefix('penenun')->group(function(){
            Route::get('/target', [ProduksiPenenunController::class, 'targets']);
            Route::get('/riwayat', [ProduksiPenenunController::class, 'riwayat']);
            Route::post('/target/{target}/hasil', [ProduksiPenenunController::class, 'simpan']);
        });
        Route::middleware('peran:admin')->prefix('admin')->group(function(){
            Route::get('/penenun',[PenenunController::class,'index']);
            Route::get('/penenun/{penenun}',[PenenunController::class,'show']);
            Route::post('/produk',[ProdukController::class,'store']);
            Route::put('/produk/{produk}',[ProdukController::class,'update']);
            Route::delete('/produk/{produk}',[ProdukController::class,'destroy']);
            });
    });
    Route::post('/midtrans/webhook', [
        MidtransWebhookController::class,
        'handle',
    ]);
});
