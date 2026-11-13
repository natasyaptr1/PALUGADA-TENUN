<?php

namespace App\Services;

use App\Exceptions\OngkirException;
use App\Exceptions\OngkirSystemFailureException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OngkirService
{
    /**
     * Cari wilayah tujuan pengiriman domestik berdasarkan kata kunci.
     *
     * @param string $kata Kata kunci pencarian (minimal 3 karakter)
     * @param int $batas Jumlah maksimal hasil (1 - 20, default 10)
     * @return array<int, array{id: string, label: string, provinsi: string, kota: string, kecamatan: string, kelurahan: string, kode_pos: string}>
     * @throws OngkirException
     */
    public function cariTujuan(string $kata, int $batas = 10): array
    {
        $kata = trim($kata);
        if (mb_strlen($kata) < 3) {
            return [];
        }

        $apiKey = (string) config('ongkir.api_key');
        if ($apiKey === '') {
            throw new OngkirException('Konfigurasi API ongkos kirim belum lengkap (kunci API belum diatur).');
        }

        $batas = max(1, min(20, $batas));
        $cacheKey = 'ongkir:tujuan:' . mb_strtolower($kata) . ':' . $batas;
        $ttlMenit = (int) config('ongkir.cache_tujuan_menit', 10080);

        $hasil = Cache::remember($cacheKey, now()->addMinutes($ttlMenit), function () use ($kata, $batas) {
            return $this->requestCariTujuan($kata, $batas);
        });

        // Hasil kosong di-cache hanya selama 60 menit
        if (empty($hasil)) {
            Cache::put($cacheKey, [], now()->addMinutes(60));
        }

        return $hasil;
    }

    /**
     * Hitung estimasi ongkos kirim ke tujuan berdasarkan berat dalam gram.
     *
     * @param string $tujuanId ID tujuan dari API ongkir
     * @param int $beratGram Berat dalam satuan gram (>= 1)
     * @param array<int, string>|null $kurir Daftar kode kurir (null untuk menggunakan semua di config)
     * @return array<int, array{kurir_kode: string, kurir_nama: string, layanan: string, deskripsi: string, biaya: int, estimasi: string}>
     * @throws OngkirException
     */
    public function hitung(string $tujuanId, int $beratGram, ?array $kurir = null): array
    {
        // 1. Validasi konfigurasi sistem
        $apiKey = (string) config('ongkir.api_key');
        $asalId = (string) config('ongkir.asal_id');

        if ($apiKey === '') {
            throw new OngkirException('Konfigurasi API ongkos kirim belum lengkap (kunci API belum diatur).');
        }

        if ($asalId === '') {
            throw new OngkirException('Konfigurasi asal pengiriman toko belum diatur.');
        }

        // 2. Validasi input pengguna
        $tujuanId = trim($tujuanId);
        if ($tujuanId === '' || $beratGram < 1) {
            throw new OngkirException('Tujuan pengiriman dan berat produk minimal 1 gram wajib diisi.');
        }

        // 3. Filter daftar kurir yang didukung
        $configKurir = array_values(array_filter(array_map('strtolower', (array) config('ongkir.kurir', []))));
        if ($kurir !== null) {
            $kurirDicari = array_map('strtolower', $kurir);
            $kurirAktif = array_values(array_filter($kurirDicari, fn ($k) => in_array($k, $configKurir, true)));
        } else {
            $kurirAktif = $configKurir;
        }

        if (empty($kurirAktif)) {
            return [];
        }

        // 4. Pembulatan berat ke atas ke kelipatan 100 gram
        $beratBulat = (int) (ceil($beratGram / 100) * 100);

        // 5. Permintaan ke API kurir satu per satu
        $baseUrl = rtrim((string) config('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1'), '/');
        $timeout = (int) config('ongkir.timeout_detik', 10);
        $ttlMenit = (int) config('ongkir.cache_ongkir_menit', 1440);

        $semuaOpsi = [];
        $jumlahGagalSistem = 0;
        $totalKurir = count($kurirAktif);

        foreach ($kurirAktif as $kodeKurir) {
            $cacheKey = "ongkir:biaya:{$asalId}:{$tujuanId}:{$beratBulat}:{$kodeKurir}";

            if (Cache::has($cacheKey)) {
                $cached = (array) Cache::get($cacheKey);
                $semuaOpsi = array_merge($semuaOpsi, $cached);
                continue;
            }

            try {
                $hasilKurir = $this->requestHitungKurir(
                    $baseUrl,
                    $apiKey,
                    $asalId,
                    $tujuanId,
                    $beratBulat,
                    $kodeKurir,
                    $timeout
                );

                // Opsi kurir (atau array kosong jika kurir tidak memiliki layanan) disimpan di cache
                Cache::put($cacheKey, $hasilKurir, now()->addMinutes($ttlMenit));
                $semuaOpsi = array_merge($semuaOpsi, $hasilKurir);
            } catch (OngkirSystemFailureException $e) {
                $jumlahGagalSistem++;
                Log::warning($this->bersihkanPesanLog("Kegagalan sistem API ongkir kurir {$kodeKurir}: {$e->getMessage()}"));
            }
        }

        // Jika seluruh kurir yang diminta mengalami kegagalan sistem
        if ($jumlahGagalSistem === $totalKurir && $totalKurir > 0) {
            throw new OngkirException('Layanan ongkir sedang tidak dapat dihubungi. Silakan coba lagi.');
        }

        // Urutkan opsi: biaya termurah dahulu, lalu nama kurir
        usort($semuaOpsi, function (array $a, array $b): int {
            if ($a['biaya'] !== $b['biaya']) {
                return $a['biaya'] <=> $b['biaya'];
            }
            return strcasecmp($a['kurir_nama'], $b['kurir_nama']);
        });

        return $semuaOpsi;
    }

    /**
     * Temukan opsi pengiriman spesifik berdasarkan kode kurir dan nama layanan.
     *
     * @param string $tujuanId
     * @param int $beratGram
     * @param string $kurirKode
     * @param string $layanan
     * @return array{kurir_kode: string, kurir_nama: string, layanan: string, deskripsi: string, biaya: int, estimasi: string}|null
     * @throws OngkirException
     */
    public function temukanOpsi(string $tujuanId, int $beratGram, string $kurirKode, string $layanan): ?array
    {
        $opsi = $this->hitung($tujuanId, $beratGram, [$kurirKode]);

        foreach ($opsi as $item) {
            if (strcasecmp($item['layanan'], $layanan) === 0) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Eksekusi HTTP request ke endpoint pencarian tujuan domestik.
     *
     * @param string $kata
     * @param int $batas
     * @return array
     * @throws OngkirException
     */
    protected function requestCariTujuan(string $kata, int $batas): array
    {
        $apiKey = (string) config('ongkir.api_key');
        $baseUrl = rtrim((string) config('ongkir.base_url', 'https://rajaongkir.komerce.id/api/v1'), '/');
        $timeout = (int) config('ongkir.timeout_detik', 10);
        $url = "{$baseUrl}/destination/domestic-destination";

        try {
            $response = Http::withHeaders(['key' => $apiKey])
                ->timeout($timeout)
                ->retry(2, 100, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->get($url, [
                    'search' => $kata,
                    'limit' => $batas,
                    'offset' => 0,
                ]);
        } catch (ConnectionException $e) {
            Log::warning($this->bersihkanPesanLog("Koneksi API tujuan ongkir gagal: {$e->getMessage()}"));
            throw new OngkirException('Layanan pencarian tujuan pengiriman sedang tidak dapat dihubungi. Silakan coba beberapa saat lagi.');
        } catch (Throwable $e) {
            Log::warning($this->bersihkanPesanLog("Kesalahan saat memanggil API tujuan ongkir: {$e->getMessage()}"));
            throw new OngkirException('Terjadi kendala saat mencari tujuan pengiriman.');
        }

        $status = $response->status();
        $json = $response->json();
        $pesanMeta = is_array($json) ? (string) ($json['meta']['message'] ?? '') : '';

        // Deteksi jika API menolak kunci (status 401/403 atau pesan meta terkait api key)
        if ($status === 401 || $status === 403 || stripos($pesanMeta, 'api key') !== false || stripos($pesanMeta, 'key not found') !== false) {
            Log::warning($this->bersihkanPesanLog("API tujuan ongkir menolak kunci API (status {$status}): {$response->body()}"));
            throw new OngkirException('Kunci API ongkos kirim tidak valid atau belum terdaftar pada server RajaOngkir/Komerce.');
        }

        // 404 dianggap data tidak ditemukan (bukan error sistem)
        if ($status === 404) {
            return [];
        }

        // 200: respons berhasil
        if ($status === 200) {
            $data = $json['data'] ?? [];

            if (empty($data) || !is_array($data)) {
                return [];
            }

            $hasil = [];
            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $hasil[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'provinsi' => (string) ($item['province_name'] ?? ''),
                    'kota' => (string) ($item['city_name'] ?? ''),
                    'kecamatan' => (string) ($item['district_name'] ?? ''),
                    'kelurahan' => (string) ($item['subdistrict_name'] ?? ''),
                    'kode_pos' => (string) ($item['zip_code'] ?? ''),
                ];
            }

            return $hasil;
        }

        // 422: parameter tidak valid
        if ($status === 422) {
            Log::warning($this->bersihkanPesanLog("Parameter tujuan ongkir tidak valid (422): {$response->body()}"));
            throw new OngkirException('Parameter pencarian tujuan pengiriman tidak valid.');
        }

        // Status lain (5xx, dll)
        Log::warning($this->bersihkanPesanLog("API tujuan ongkir mengembalikan status {$status}: {$response->body()}"));
        throw new OngkirException('Layanan pencarian tujuan pengiriman mengalami gangguan. Silakan coba lagi.');
    }

    /**
     * Eksekusi HTTP request ke endpoint kalkulasi biaya ongkir untuk satu kurir.
     *
     * @param string $baseUrl
     * @param string $apiKey
     * @param string $asalId
     * @param string $tujuanId
     * @param int $beratBulat
     * @param string $kodeKurir
     * @param int $timeout
     * @return array
     * @throws OngkirSystemFailureException
     */
    protected function requestHitungKurir(
        string $baseUrl,
        string $apiKey,
        string $asalId,
        string $tujuanId,
        int $beratBulat,
        string $kodeKurir,
        int $timeout
    ): array {
        $url = "{$baseUrl}/calculate/domestic-cost";

        try {
            $response = Http::withHeaders(['key' => $apiKey])
                ->timeout($timeout)
                ->retry(2, 100, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->asForm()
                ->post($url, [
                    'origin' => $asalId,
                    'destination' => $tujuanId,
                    'weight' => $beratBulat,
                    'courier' => $kodeKurir,
                ]);
        } catch (ConnectionException $e) {
            throw new OngkirSystemFailureException("Koneksi gagal: {$e->getMessage()}", 0, $e);
        } catch (Throwable $e) {
            throw new OngkirSystemFailureException("Error permintaan HTTP: {$e->getMessage()}", 0, $e);
        }

        $status = $response->status();

        // 400 (layanan tidak tersedia untuk rute ini) atau 422 (kode kurir tidak valid untuk rute)
        if ($status === 400 || $status === 422) {
            return [];
        }

        // 200: respons sukses
        if ($status === 200) {
            $json = $response->json();
            if (!is_array($json)) {
                throw new OngkirSystemFailureException('Format respons API tidak valid.');
            }

            // Jika meta.code bernilai 400 / 422 di dalam payload HTTP 200
            $metaCode = $json['meta']['code'] ?? null;
            if ($metaCode === 400 || $metaCode === 422) {
                return [];
            }

            $data = $json['data'] ?? [];
            if (empty($data) || !is_array($data)) {
                return [];
            }

            $opsi = [];
            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                // Abaikan jika cost bukan angka atau bernilai negatif
                if (!isset($item['cost']) || !is_numeric($item['cost']) || (float) $item['cost'] < 0) {
                    continue;
                }

                $opsi[] = [
                    'kurir_kode' => (string) ($item['code'] ?? $kodeKurir),
                    'kurir_nama' => (string) ($item['name'] ?? strtoupper($kodeKurir)),
                    'layanan' => (string) ($item['service'] ?? ''),
                    'deskripsi' => (string) ($item['description'] ?? ''),
                    'biaya' => (int) round((float) $item['cost']),
                    'estimasi' => !empty($item['etd']) ? (string) $item['etd'] : '-',
                ];
            }

            return $opsi;
        }

        // Status 401, 403, 5xx, atau selain yang ditangani di atas merupakan kegagalan sistem
        throw new OngkirSystemFailureException("API ongkir mengembalikan status {$status}: {$response->body()}");
    }

    /**
     * Bersihkan teks log agar kunci API tidak pernah tercatat di file log.
     *
     * @param string $pesan
     * @return string
     */
    protected function bersihkanPesanLog(string $pesan): string
    {
        $apiKey = (string) config('ongkir.api_key');
        if ($apiKey !== '') {
            $pesan = str_replace($apiKey, '***', $pesan);
        }

        return $pesan;
    }
}

