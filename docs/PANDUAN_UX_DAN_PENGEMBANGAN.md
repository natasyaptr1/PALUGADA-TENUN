# Panduan UX dan Pengembangan Berkelanjutan

## Tujuan revisi
Tampilan dipisahkan berdasarkan kebutuhan pengguna agar orang awam tidak melihat istilah teknis yang tidak diperlukan.

## Pembeli
Setelah login, pembeli diarahkan ke **Akun Saya**, bukan kembali ke halaman yang sama seperti pengunjung. Area ini menyediakan akses ke Koleksi, Profil, Keranjang, dan Pesanan Saya.

Keranjang dan Pesanan sudah memiliki route dan halaman kosong yang ramah pengguna. Saat fungsi transaksi ditambahkan, implementasi cukup mengisi halaman/route tersebut tanpa mengubah navigasi utama.

## Admin
Admin menggunakan sidebar tetap:
1. Beranda
2. Produk & Stok
3. Penenun
4. Target Produksi
5. Pesanan
6. Pembayaran
7. Laporan

Menu yang belum mempunyai logika bisnis menampilkan halaman placeholder yang konsisten, bukan label sprint atau istilah teknis.

## Penenun
Penenun diarahkan ke aplikasi Android. Website penenun berfungsi sebagai halaman pengantar akun, sedangkan aktivitas lapangan dilakukan pada Flutter.

Flutter menggunakan API Laravel yang sama. Struktur login dan penyimpanan token sudah disiapkan. Pengembangan target produksi berikutnya cukup menambahkan endpoint dan screen baru.

## Aturan saat melanjutkan fitur
- Jangan membuat project baru untuk sprint berikutnya.
- Jangan mengubah nama route utama jika tidak perlu.
- Tambahkan controller/service/view pada modul yang sudah disiapkan.
- Pertahankan satu database terpusat.
- Pertahankan istilah antarmuka Bahasa Indonesia yang sederhana.
- Istilah teknis hanya ditaruh pada dokumentasi developer, bukan halaman pembeli.
