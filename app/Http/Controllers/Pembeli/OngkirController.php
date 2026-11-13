<?php

namespace App\Http\Controllers\Pembeli;

use App\Exceptions\OngkirException;
use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Services\OngkirService;
use App\Services\PengirimanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OngkirController extends Controller
{
    /**
     * Konstruktor controller endpoint ongkos kirim.
     */
    public function __construct(
        protected OngkirService $ongkirService,
        protected PengirimanService $pengirimanService
    ) {}

    /**
     * Cari wilayah tujuan domestik berdasarkan kata kunci pencarian.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tujuan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string', 'min:3', 'max:60'],
        ], [
            'q.required' => 'Kata kunci pencarian tujuan wajib diisi.',
            'q.string' => 'Kata kunci pencarian tujuan harus berupa teks.',
            'q.min' => 'Kata kunci pencarian tujuan minimal 3 karakter.',
            'q.max' => 'Kata kunci pencarian tujuan maksimal 60 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'pesan' => $validator->errors()->first(),
            ], 422);
        }

        $kata = trim((string) $request->input('q'));

        try {
            $hasil = $this->ongkirService->cariTujuan($kata);
        } catch (OngkirException $e) {
            return $this->tanganiOngkirException($e);
        }

        return response()->json([
            'data' => $hasil,
        ], 200);
    }

    /**
     * Hitung opsi tarif ongkos kirim berdasarkan keranjang aktif pembeli.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function opsi(Request $request): JsonResponse
    {
        // 1. Validasi input
        $validator = Validator::make($request->all(), [
            'tujuan_id' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:30'],
            'kurir' => ['nullable', 'string', 'max:40'],
        ], [
            'tujuan_id.required' => 'ID tujuan pengiriman wajib diisi.',
            'tujuan_id.string' => 'ID tujuan pengiriman harus berupa teks angka.',
            'tujuan_id.regex' => 'ID tujuan pengiriman harus berupa angka.',
            'tujuan_id.max' => 'ID tujuan pengiriman maksimal 30 karakter.',
            'kurir.string' => 'Pilihan kurir harus berupa teks.',
            'kurir.max' => 'Pilihan kurir maksimal 40 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'pesan' => $validator->errors()->first(),
            ], 422);
        }

        // 2. Olah parameter kurir opsional (dipisahkan koma, hanya huruf dan angka)
        $kurirParam = $request->input('kurir');
        $kurirArray = null;

        if ($kurirParam !== null && trim((string) $kurirParam) !== '') {
            $rawList = explode(',', (string) $kurirParam);
            $parsedKurir = [];

            foreach ($rawList as $rawCode) {
                $code = trim($rawCode);
                if ($code === '') {
                    continue;
                }

                if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
                    return response()->json([
                        'pesan' => 'Format kode kurir tidak valid. Hanya boleh berupa huruf dan angka.',
                    ], 422);
                }

                $parsedKurir[] = strtolower($code);
            }

            if (!empty($parsedKurir)) {
                $kurirArray = array_values(array_unique($parsedKurir));
            }
        }

        // 3. Ambil keranjang AKTIF milik pembeli yang login
        $pengguna = $request->user();

        $keranjang = Keranjang::query()
            ->where('pembeli_id', $pengguna->id)
            ->where('status', 'aktif')
            ->with(['detail.produk'])
            ->latest('id')
            ->first();

        if (!$keranjang || $keranjang->detail->isEmpty()) {
            return response()->json([
                'pesan' => 'Keranjang masih kosong.',
            ], 422);
        }

        // 4. Hitung ringkasan berat dan subtotal dari keranjang
        $ringkasan = $this->pengirimanService->ringkasKeranjang($keranjang->detail);

        if ($ringkasan['berat_gram'] <= 0) {
            return response()->json([
                'pesan' => 'Keranjang masih kosong.',
            ], 422);
        }

        $beratTotalGram = (int) $ringkasan['berat_gram'];
        $subtotal = (float) $ringkasan['subtotal'];
        $tujuanId = (string) $request->input('tujuan_id');

        // 5. Panggil perhitungan opsi ke OngkirService
        try {
            $daftarOpsi = $this->ongkirService->hitung($tujuanId, $beratTotalGram, $kurirArray);
        } catch (OngkirException $e) {
            return $this->tanganiOngkirException($e);
        }

        // 6. Format respons JSON
        if (empty($daftarOpsi)) {
            return response()->json([
                'data' => [
                    'berat_total_gram' => $beratTotalGram,
                    'subtotal' => $subtotal,
                    'opsi' => [],
                ],
                'pesan' => 'Tidak ada layanan pengiriman untuk tujuan ini.',
            ], 200);
        }

        return response()->json([
            'data' => [
                'berat_total_gram' => $beratTotalGram,
                'subtotal' => $subtotal,
                'opsi' => $daftarOpsi,
            ],
        ], 200);
    }

    /**
     * Tangani exception dari OngkirService dan konversi menjadi respons JSON yang ramah.
     *
     * @param OngkirException $e
     * @return JsonResponse
     */
    protected function tanganiOngkirException(OngkirException $e): JsonResponse
    {
        $pesan = $e->getMessage();
        $apiKey = (string) config('ongkir.api_key');
        $pesanLog = $apiKey !== '' ? str_replace($apiKey, '***', $pesan) : $pesan;
        Log::warning('Kegagalan layanan ongkir: ' . $pesanLog);

        $isInputTidakValid = str_contains($pesan, 'wajib diisi')
            || str_contains(strtolower($pesan), 'tidak valid');

        if ($isInputTidakValid) {
            return response()->json([
                'pesan' => $pesan,
            ], 422);
        }

        return response()->json([
            'pesan' => 'Layanan ongkir sedang tidak tersedia. Silakan coba lagi.',
        ], 503);
    }
}

