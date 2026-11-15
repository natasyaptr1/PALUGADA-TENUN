<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnkripsiKeahlianController extends Controller
{
    // Mengambil data terenkripsi dari tabel keahlian
    public function index()
    {
        $data = DB::table('keahlian')
            ->where('nama_keahlian', 'like', 'PML-ENC-%')
            ->orderByDesc('id')
            ->get(['id', 'nama_keahlian', 'keterangan']);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Menyimpan ciphertext hasil enkripsi Android
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ciphertext' => 'required|string|max:60000',
        ]);

        $id = DB::table('keahlian')->insertGetId([
            'nama_keahlian' => 'PML-ENC-' . strtoupper(
                bin2hex(random_bytes(6))
            ),
            'keterangan' => $validated['ciphertext'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ciphertext berhasil disimpan.',
            'id' => $id
        ], 201);
    }
}