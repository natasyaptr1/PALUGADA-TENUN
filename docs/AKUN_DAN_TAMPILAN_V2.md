# Revisi UX dan Akun V2

Project tetap satu aplikasi Laravel yang sama. Pengembangan berikutnya cukup menambah fungsi pada menu yang sudah tersedia.

## Pembagian akun

- **Pembeli** mendaftar sendiri melalui halaman Daftar. Setelah login diarahkan ke `Akun Saya`.
- **Admin** tidak mendaftar dari halaman publik. Akun admin dibuat melalui seeder dan setelah login diarahkan ke `/admin/beranda`.
- **Penenun** dibuat oleh admin dari menu Data Penenun. Akun tersebut dipakai untuk aplikasi Android/Flutter penenun.

## Akun admin lokal

Default pengembangan:

- Email: `admin@palugadabandar.id`
- Password: `PalugadaAdmin123!`

Nilai bisa diganti dari `.env` menggunakan `ADMIN_EMAIL` dan `ADMIN_PASSWORD` sebelum menjalankan seeder.

Jalankan tanpa menghapus data:

```powershell
php artisan db:seed --class=DatabaseSeeder
```

Jangan memakai `migrate:fresh` pada database yang sudah berisi data.

## Perubahan tampilan

- Dashboard admin dipisahkan jelas dari website pembeli.
- Admin memiliki identitas, email, dan tombol Keluar yang selalu terlihat.
- Halaman akun pembeli dibuat lebih ringkas dan tidak lagi memakai hero yang terlalu tinggi.
- Halaman akun pembeli memakai navigasi khusus: Belanja, Akun Saya, Pesanan, Keranjang, Profil, dan Keluar.
- Footer akun pembeli dibuat ringkas supaya halaman tidak terasa terlalu panjang.
- Istilah pengembangan seperti sprint, API, endpoint, dan struktur sistem tidak ditampilkan ke pembeli.
- Menu yang fungsinya belum aktif tetap berada di posisi final agar pengembangan berikutnya tinggal menambahkan fungsi.
