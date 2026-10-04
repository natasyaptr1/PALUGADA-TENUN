# Cara Memasang Revisi ke Project yang Sudah Berjalan

Revisi ini tidak mengubah struktur tabel database, jadi **tidak perlu migrate ulang**.

## Cara aman
1. Backup project lama.
2. Ekstrak file patch.
3. Copy seluruh isi patch ke folder project Laravel yang sedang dipakai.
4. Pilih **Replace files in destination**.
5. Jangan hapus `.env`, `vendor`, `storage`, atau database yang sudah berjalan.
6. Jalankan:

```powershell
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan serve
```

## Hasil perubahan
- Pembeli setelah login diarahkan ke **Akun Saya**.
- Pembeli mempunyai halaman Profil, Keranjang, dan Pesanan yang sudah masuk ke alur navigasi.
- Admin tidak lagi melihat istilah Sprint/API pada halaman utama.
- Menu Admin untuk pengembangan lanjutan sudah tetap pada satu sidebar.
- Penenun mempunyai area sendiri dan source Flutter Android tersedia di folder `mobile_penenun`.
- Database tidak diubah.

## Pengembangan berikutnya
Untuk Target Produksi, cukup isi route/controller/view yang sudah disiapkan dan tambahkan endpoint mobile. Tidak perlu membuat project baru.
