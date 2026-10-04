# REST API v1 - Sprint 1 (Bahasa Indonesia)

Base URL: `/api/v1`

## Endpoint publik

- `GET /status` - status API.
- `POST /autentikasi/daftar` - registrasi Pembeli.
- `POST /autentikasi/masuk` - login dan membuat token Sanctum.
- `GET /produk` - daftar produk aktif + stok.
- `GET /produk/{produk}` - detail produk aktif + stok.

Contoh registrasi:
```json
{
  "nama": "Sinta",
  "email": "sinta@example.com",
  "no_hp": "081234567890",
  "kata_sandi": "rahasia123",
  "kata_sandi_confirmation": "rahasia123",
  "nama_perangkat": "android-sinta"
}
```

## Endpoint terautentikasi

Bearer token Sanctum.

- `GET /autentikasi/saya`
- `POST /autentikasi/keluar`

## Endpoint Admin

- `GET /admin/penenun`
- `GET /admin/penenun/{penenun}`
- `POST /admin/produk`
- `PUT /admin/produk/{produk}`
- `DELETE /admin/produk/{produk}`

Field produk: `kode_produk`, `nama_produk`, `jenis`, `motif`, `warna`, `ukuran`, `material`, `harga`, `foto`, `status`.

Contoh respons daftar produk:
```json
{
  "data": [],
  "meta": {
    "halaman_sekarang": 1,
    "per_halaman": 12,
    "total": 0,
    "halaman_terakhir": 1
  }
}
```
