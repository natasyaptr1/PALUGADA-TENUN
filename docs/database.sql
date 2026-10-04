-- Database Sistem Informasi Manajemen Produksi dan Penjualan Tenun Ikat Palugada Bandar
-- Nama tabel dan atribut bisnis menggunakan Bahasa Indonesia.
-- Kolom Laravel teknis created_at/updated_at/deleted_at dan tabel personal_access_tokens tetap mengikuti konvensi framework.

CREATE TABLE pengguna (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  no_hp VARCHAR(20) NULL,
  alamat TEXT NULL,
  kata_sandi VARCHAR(255) NOT NULL,
  peran ENUM('admin','penenun','pembeli') NOT NULL DEFAULT 'pembeli',
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
CREATE TABLE profil_penenun (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pengguna_id BIGINT UNSIGNED NOT NULL UNIQUE,
  kode_penenun VARCHAR(30) NOT NULL UNIQUE,
  keahlian VARCHAR(100) NULL,
  catatan TEXT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_profil_penenun_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE
);
CREATE TABLE produk (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_produk VARCHAR(50) NOT NULL UNIQUE,
  nama_produk VARCHAR(100) NOT NULL,
  jenis VARCHAR(50) NOT NULL,
  motif VARCHAR(100) NULL, warna VARCHAR(50) NULL, ukuran VARCHAR(50) NULL, material VARCHAR(100) NULL,
  harga DECIMAL(12,2) NOT NULL,
  foto VARCHAR(255) NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
);
CREATE TABLE target_produksi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produk_id BIGINT UNSIGNED NOT NULL,
  penenun_id BIGINT UNSIGNED NOT NULL,
  jumlah_target INT UNSIGNED NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_deadline DATE NULL,
  status ENUM('berjalan','selesai','dibatalkan') NOT NULL DEFAULT 'berjalan',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (produk_id) REFERENCES produk(id),
  FOREIGN KEY (penenun_id) REFERENCES pengguna(id)
);
CREATE TABLE hasil_produksi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  target_produksi_id BIGINT UNSIGNED NOT NULL,
  jumlah_selesai INT UNSIGNED NOT NULL,
  tanggal_input DATETIME NOT NULL,
  catatan TEXT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (target_produksi_id) REFERENCES target_produksi(id) ON DELETE CASCADE
);
CREATE TABLE stok (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produk_id BIGINT UNSIGNED NOT NULL UNIQUE,
  jumlah_stok INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);
CREATE TABLE keranjang (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pembeli_id BIGINT UNSIGNED NOT NULL,
  status ENUM('aktif','checkout') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (pembeli_id) REFERENCES pengguna(id) ON DELETE CASCADE
);
CREATE TABLE detail_keranjang (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  keranjang_id BIGINT UNSIGNED NOT NULL,
  produk_id BIGINT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uk_detail_keranjang (keranjang_id, produk_id),
  FOREIGN KEY (keranjang_id) REFERENCES keranjang(id) ON DELETE CASCADE,
  FOREIGN KEY (produk_id) REFERENCES produk(id)
);
CREATE TABLE pesanan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pembeli_id BIGINT UNSIGNED NOT NULL,
  keranjang_id BIGINT UNSIGNED NOT NULL UNIQUE,
  kode_pesanan VARCHAR(40) NOT NULL UNIQUE,
  tanggal_pesanan DATETIME NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  status_pesanan ENUM('menunggu_pembayaran','dibayar','diproses','selesai','batal') NOT NULL DEFAULT 'menunggu_pembayaran',
  nama_penerima VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  alamat_pengiriman TEXT NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (pembeli_id) REFERENCES pengguna(id),
  FOREIGN KEY (keranjang_id) REFERENCES keranjang(id)
);
CREATE TABLE detail_pesanan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pesanan_id BIGINT UNSIGNED NOT NULL,
  produk_id BIGINT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  FOREIGN KEY (produk_id) REFERENCES produk(id)
);
CREATE TABLE pembayaran (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pesanan_id BIGINT UNSIGNED NOT NULL,
  metode ENUM('cash','midtrans') NOT NULL,
  id_transaksi VARCHAR(100) NULL UNIQUE,
  tipe_pembayaran VARCHAR(50) NULL,
  jumlah_bayar DECIMAL(12,2) NOT NULL,
  status_pembayaran ENUM('menunggu','berhasil','gagal','kedaluwarsa') NOT NULL DEFAULT 'menunggu',
  dikonfirmasi_oleh BIGINT UNSIGNED NULL,
  tanggal_bayar DATETIME NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  FOREIGN KEY (dikonfirmasi_oleh) REFERENCES pengguna(id) ON DELETE SET NULL
);
CREATE TABLE mutasi_stok (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stok_id BIGINT UNSIGNED NOT NULL,
  jenis_mutasi ENUM('masuk','keluar','penyesuaian') NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  sumber ENUM('produksi','penjualan','non_penjualan') NOT NULL,
  hasil_produksi_id BIGINT UNSIGNED NULL,
  detail_pesanan_id BIGINT UNSIGNED NULL,
  catatan TEXT NULL,
  tanggal_mutasi DATETIME NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (stok_id) REFERENCES stok(id) ON DELETE CASCADE,
  FOREIGN KEY (hasil_produksi_id) REFERENCES hasil_produksi(id) ON DELETE SET NULL,
  FOREIGN KEY (detail_pesanan_id) REFERENCES detail_pesanan(id) ON DELETE SET NULL
);

-- Tabel teknis Laravel Sanctum untuk token API.
CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_tokenable (tokenable_type, tokenable_id)
);
