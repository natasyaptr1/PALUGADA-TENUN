# Panduan Update UX V3

Patch ini dibuat untuk project yang sudah berjalan di:

```text
D:\Laragon\www\palugada-tenun
```

1. Ekstrak ZIP patch.
2. Copy **isi** folder patch ke `D:\Laragon\www\palugada-tenun`.
3. Pilih **Replace files in the destination**.
4. Jangan mengganti `.env`, jangan menghapus database, dan jangan menjalankan `migrate:fresh`.
5. Jalankan:

```powershell
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan serve
```

Jika akun admin belum ada, jalankan sekali:

```powershell
php artisan db:seed --class=DatabaseSeeder
```

Akun admin default mengikuti nilai `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env` / `.env.example`.
