# Dokumentasi API Target Produksi Penenun (Flutter)

Dokumentasi ini ditujukan bagi tim pengembang aplikasi mobile Flutter untuk penenun. API ini memungkinkan penenun melihat ringkasan target produksi, daftar target yang ditugaskan kepada mereka, serta riwayat pencatatan hasil produksi.

---

## 1. Konfigurasi Dasar

- **Base URL**: `http://<domain-atau-ip>:8000/api/v1`
- **Header Wajib**:
  ```http
  Authorization: Bearer <token_sanctum_penenun>
  Accept: application/json
  ```
  > Token diperoleh melalui endpoint masuk `/api/v1/autentikasi/masuk` dengan akun berstatus dan berpenan `penenun`.

---

## 2. Daftar Endpoint

| Metode | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/v1/penenun/target/ringkasan` | Mengambil ringkasan jumlah target penenun login (berjalan, selesai, dibatalkan, terlambat) |
| `GET` | `/api/v1/penenun/target` | Mengambil daftar target penenun login dengan filter, paginasi, dan pengurutan cerdas |
| `GET` | `/api/v1/penenun/target/{id}` | Mengambil detail target produksi beserta riwayat pencatatan hasil |

---

## 3. Spesifikasi Endpoint

### A. Ringkasan Target Penenun
- **Endpoint**: `GET /api/v1/penenun/target/ringkasan`
- **Akses**: Hanya Penenun yang sedang login
- **Respons Sukses (200 OK)**:
```json
{
  "data": {
    "berjalan": 3,
    "selesai": 5,
    "dibatalkan": 1,
    "terlambat": 1
  }
}
```

---

### B. Daftar Target Produksi
- **Endpoint**: `GET /api/v1/penenun/target`
- **Query Parameter**:
  | Parameter | Tipe | Default | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `status` | string | `null` | Pilihan: `berjalan`, `selesai`, `dibatalkan`, `terlambat`. Nilai selain itu akan menampilkan semua target. |
  | `per_halaman` | integer | `10` | Jumlah data per halaman (minimal `1`, maksimal `50`). |
  | `page` / `halaman` | integer | `1` | Nomor halaman data. |

- **Aturan Pengurutan**:
  1. Target dengan status `berjalan` ditampilkan lebih dulu.
  2. Di antara target `berjalan`, target dengan batas waktu terdekat diletakkan paling atas, diikuti target yang tidak memiliki batas waktu khusus.
  3. Setelah itu, target dengan status lain (`selesai`, `dibatalkan`) diurutkan dari yang terbaru dibuat.

- **Respons Sukses (200 OK)**:
```json
{
  "data": [
    {
      "id": 12,
      "produk": {
        "id": 4,
        "kode_produk": "PRD-004",
        "nama_produk": "Kain Tenun Troso Baron Ikat",
        "jenis": "Kain Tenun",
        "motif": "Baron Geometris",
        "warna": "Hijau Sage",
        "ukuran": "240 x 115 cm",
        "material": "Katun Viscose Halus",
        "foto_url": "http://localhost:8000/storage/produk/troso-baron.jpg"
      },
      "jumlah_target": 20,
      "total_selesai": 15,
      "sisa_target": 5,
      "persen_progres": 75,
      "tanggal_mulai": "2026-10-10",
      "tanggal_deadline": "2026-10-25",
      "status": "berjalan",
      "terlambat": false,
      "hari_terlambat": 0,
      "dibuat_pada": "2026-10-10T08:30:00+07:00"
    },
    {
      "id": 8,
      "produk": {
        "id": 2,
        "kode_produk": "PRD-002",
        "nama_produk": "Kain Tenun Blanket Etnik",
        "jenis": "Blanket",
        "motif": "Toraja Etnik",
        "warna": "Cokelat Terakota",
        "ukuran": "240 x 120 cm",
        "material": "Benang Katun Tebal",
        "foto_url": null
      },
      "jumlah_target": 10,
      "total_selesai": 4,
      "sisa_target": 6,
      "persen_progres": 40,
      "tanggal_mulai": "2026-10-01",
      "tanggal_deadline": "2026-10-15",
      "status": "berjalan",
      "terlambat": true,
      "hari_terlambat": 3,
      "dibuat_pada": "2026-10-01T09:00:00+07:00"
    }
  ],
  "meta": {
    "halaman_sekarang": 1,
    "per_halaman": 10,
    "total": 2,
    "halaman_terakhir": 1
  }
}
```

---

### C. Detail Target Produksi
- **Endpoint**: `GET /api/v1/penenun/target/{id}`
- **Keamanan**: Penenun hanya dapat membuka target yang ditugaskan kepada akunnya. Membuka ID milik penenun lain akan menghasilkan error 404 (bukan 403) untuk menjaga privasi ID.
- **Respons Sukses (200 OK)**:
```json
{
  "data": {
    "id": 12,
    "produk": {
      "id": 4,
      "kode_produk": "PRD-004",
      "nama_produk": "Kain Tenun Troso Baron Ikat",
      "jenis": "Kain Tenun",
      "motif": "Baron Geometris",
      "warna": "Hijau Sage",
      "ukuran": "240 x 115 cm",
      "material": "Katun Viscose Halus",
      "foto_url": "http://localhost:8000/storage/produk/troso-baron.jpg"
    },
    "jumlah_target": 20,
    "total_selesai": 15,
    "sisa_target": 5,
    "persen_progres": 75,
    "tanggal_mulai": "2026-10-10",
    "tanggal_deadline": "2026-10-25",
    "status": "berjalan",
    "terlambat": false,
    "hari_terlambat": 0,
    "dibuat_pada": "2026-10-10T08:30:00+07:00",
    "riwayat_hasil": [
      {
        "id": 101,
        "jumlah_selesai": 5,
        "tanggal_input": "2026-10-18T14:20:00+07:00",
        "catatan": "Selesai 5 lembar motif tengah"
      },
      {
        "id": 98,
        "jumlah_selesai": 10,
        "tanggal_input": "2026-10-14T11:00:00+07:00",
        "catatan": null
      }
    ]
  }
}
```

---

## 4. Format Respons Error

### A. 401 Unauthorized (Token Tidak Disediakan / Kedaluwarsa)
- **Status HTTP**: `401 Unauthorized`
- **Body**:
```json
{
  "message": "Unauthenticated."
}
```

### B. 403 Forbidden (Akses Bukan Akun Penenun)
- **Status HTTP**: `403 Forbidden`
- **Body**:
```json
{
  "message": "Anda tidak memiliki hak akses untuk halaman ini."
}
```

### C. 404 Not Found (Target Tidak Ditemukan atau Milik Penenun Lain)
- **Status HTTP**: `404 Not Found`
- **Body**:
```json
{
  "pesan": "Target produksi tidak ditemukan."
}
```
