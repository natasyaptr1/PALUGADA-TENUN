import 'package:flutter/material.dart';
import '../services/api_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiService.instance.daftarHasil();
  }

  Future<void> _muat() async {
    final permintaan = ApiService.instance.daftarHasil();
    if (!mounted) return;
    setState(() {
      _future = permintaan;
    });
    try {
      await permintaan;
    } catch (_) {
      // FutureBuilder akan menampilkan pesan jika server gagal.
    }
  }

  @override
  Widget build(BuildContext context) => RefreshIndicator(
        onRefresh: _muat,
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  const SizedBox(height: 100),
                  const Center(child: Text('Gagal memuat riwayat produksi.')),
                  Center(
                      child: TextButton(
                          onPressed: _muat, child: const Text('Coba Lagi'))),
                ],
              );
            }
            final items = snapshot.data ?? [];
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(18),
              children: [
                const Text('Riwayat Produksi',
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                    )),
                const SizedBox(height: 8),
                const Text('Catatan hasil tenun yang sudah kamu kirim.',
                    style: TextStyle(fontSize: 15)),
                const SizedBox(height: 18),
                if (items.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 65),
                    child: Center(
                        child: Text('Belum ada hasil produksi yang dicatat.')),
                  ),
                for (final item in items)
                  Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 9),
                      title: Text(_namaProduk(item),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          )),
                      subtitle: Text(
                        'Tanggal: ${_tanggal(item)}${_catatan(item).isEmpty ? '' : '\nCatatan: ${_catatan(item)}'}',
                        style: const TextStyle(fontSize: 14),
                      ),
                      trailing: Text('+${item['jumlah_selesai'] ?? 0} pcs',
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                          )),
                    ),
                  ),
              ],
            );
          },
        ),
      );

  String _namaProduk(Map<String, dynamic> item) {
    final target = item['target_produksi'];
    if (target is Map) {
      final produk = target['produk'];
      if (produk is Map) return '${produk['nama_produk'] ?? 'Produk Tenun'}';
    }
    return 'Produk Tenun';
  }

  String _tanggal(Map<String, dynamic> item) {
    final nilai = item['tanggal_input'] ?? item['created_at'];
    return nilai?.toString().split('T').first ?? '-';
  }

  String _catatan(Map<String, dynamic> item) =>
      '${item['catatan'] ?? ''}'.trim();
}
