# Palugada Penenun - Flutter Android

Folder ini adalah aplikasi mobile penenun yang menyatu dengan project utama melalui API Laravel yang sama.

## Yang sudah disiapkan sekarang
- Login menggunakan akun penenun dari Admin.
- Menyimpan token login pada perangkat.
- Mengambil data akun yang sedang masuk.
- Beranda mobile dengan menu Target Produksi, Catat Hasil, dan Riwayat.
- Struktur siap dilanjutkan tanpa membuat aplikasi baru saat fitur produksi ditambahkan.

## Cara membuat project Android
Pastikan Flutter SDK sudah terpasang, lalu buka terminal di folder `mobile_penenun`:

```bash
flutter create . --platforms=android
flutter pub get
flutter run
```

File di `lib/` adalah source utama yang sudah disiapkan.

## Alamat API
Default untuk Android Emulator:

```text
http://10.0.2.2:8000/api/v1
```

Jika memakai HP Android fisik, ubah `lib/config/api_config.dart` ke IP laptop pada jaringan Wi-Fi yang sama, contoh:

```text
http://192.168.1.10:8000/api/v1
```

Laravel perlu dijalankan agar bisa diakses dari jaringan, misalnya:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Pengembangan berikutnya
Saat modul produksi ditambahkan, cukup tambahkan endpoint target/hasil/riwayat pada Laravel dan screen baru di aplikasi ini. Login dan struktur aplikasi tidak perlu dibuat ulang.
