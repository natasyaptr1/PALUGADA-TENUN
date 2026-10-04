# Sistem Informasi Palugada Bandar

Satu project berkelanjutan untuk **Website Pembeli, Website Admin, REST API Laravel, dan aplikasi Android Flutter untuk Penenun**.

Project ini tidak dipisah menjadi project per sprint. Fitur berikutnya ditambahkan ke struktur yang sama supaya route, database, tampilan, dan API tetap konsisten.

## Area pengguna

### Pembeli
- Katalog publik tanpa login.
- Registrasi dan login.
- Halaman **Akun Saya** setelah login.
- Profil pembeli dapat diperbarui.
- Halaman Keranjang dan Pesanan sudah disiapkan pada navigasi agar pengembangan transaksi berikutnya tidak mengubah alur pengguna.

### Admin
- Beranda ringkasan usaha.
- CRUD Produk & Stok.
- CRUD Penenun.
- Menu Target Produksi, Pesanan, Pembayaran, dan Laporan sudah memiliki posisi tetap dan dapat diisi bertahap.

### Penenun
- Akun dibuat oleh Admin.
- Login web mengarah ke halaman pengantar penenun.
- Source Flutter berada di `mobile_penenun/`.
- Mobile sudah disiapkan untuk login akun penenun dan struktur menu Target Produksi, Catat Hasil, dan Riwayat.

## Teknologi
- Laravel + Blade
- MySQL
- Laravel Sanctum untuk API
- Flutter Android untuk Penenun

## Menjalankan Laravel

```powershell
composer install --prefer-dist --no-dev --no-progress
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000
```

Akun demo:

```text
Admin    : admin@palugada.test / password
Penenun  : penenun@palugada.test / password
```

## Menjalankan Flutter Penenun
Lihat `mobile_penenun/README.md`.

## Prinsip pengembangan
- Bahasa pada antarmuka memakai istilah yang mudah dipahami pengguna.
- Istilah teknis seperti API, database, endpoint, dan sprint tidak ditampilkan pada halaman pembeli.
- Setiap peran mempunyai area sendiri.
- Pengembangan berikutnya menambah fungsi pada menu yang sudah ada, bukan membuat project baru.
