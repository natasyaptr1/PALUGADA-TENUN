<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Pastikan pengguna sudah login
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        // 2. Hanya Admin yang boleh upload
        $peran = strtolower(
            trim((string) ($user->peran ?? ''))
        );

        if ($peran !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang boleh upload.'
            ], 403);
        }

        // 3. Validasi produk dan media
        $data = $request->validate([
            'produk_id' => [
                'required',
                'integer',
                'exists:produk,id'
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,mp4',
                'max:10240'
            ]
        ]);

        // 4. Cari produk yang dipilih
        $produk = Produk::find($data['produk_id']);

        if (!$produk) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        $file = $request->file('file');
        $mime = $file->getMimeType();

        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');

        if (!$isImage && !$isVideo) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis file tidak didukung.'
            ], 422);
        }

        // 5. Tentukan folder penyimpanan
        $folder = $isVideo
            ? 'video-produk'
            : 'foto-produk';

        $extension = $file->extension();

        $filename = Str::uuid() . '.' . $extension;

        // File dikelompokkan berdasarkan ID produk
        $path = $folder
            . '/produk-' . $produk->id
            . '/' . $filename;

        // 6. Baca konfigurasi dari Laravel
        $baseUrl = rtrim(
            (string) config('services.supabase.url'),
            '/'
        );

        $bucket = (string) config(
            'services.supabase.bucket'
        );

        $secret = (string) config(
            'services.supabase.secret_key'
        );

        if (
            $baseUrl === '' ||
            $bucket === '' ||
            $secret === ''
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Supabase belum lengkap.'
            ], 500);
        }

        // 7. URL upload Supabase Storage
        $encodedPath = implode(
            '/',
            array_map('rawurlencode', explode('/', $path))
        );

        $uploadUrl = $baseUrl
            . '/storage/v1/object/'
            . rawurlencode($bucket)
            . '/'
            . $encodedPath;

        try {
            // 8. Kirim file ke Supabase
            $response = Http::withHeaders([
                'apikey' => $secret,
                'x-upsert' => 'false'
            ])
                ->timeout(60)
                ->withBody(
                    file_get_contents($file->getRealPath()),
                    $mime
                )
                ->post($uploadUrl);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supabase menolak upload.',
                    'status_supabase' => $response->status(),
                    'detail' => $response->json('message')
                        ?? $response->json('error')
                        ?? 'Periksa pengaturan Supabase.'
                ], 502);
            }

            // 9. URL untuk menampilkan foto katalog
            $fotoUrl = null;

            if ($isImage) {
                $fotoUrl = $baseUrl
                    . '/storage/v1/object/public/'
                    . rawurlencode($bucket)
                    . '/'
                    . $encodedPath;

                // 10. Simpan URL foto ke MySQL
                $produk->update([
                    'foto' => $fotoUrl
                ]);
            }

            // Video tersimpan di Supabase,
            // tetapi tidak dimasukkan ke kolom foto.

            return response()->json([
                'success' => true,
                'message' => $isImage
                    ? 'Foto berhasil diunggah dan produk diperbarui.'
                    : 'Video berhasil diunggah ke Supabase.',
                'produk_id' => $produk->id,
                'nama_produk' => $produk->nama_produk,
                'bucket' => $bucket,
                'path' => $path,
                'foto_url' => $fotoUrl,
                'mime_type' => $mime
            ], 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses upload media.'
            ], 500);
        }
    }
}
