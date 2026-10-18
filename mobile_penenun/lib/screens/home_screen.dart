import 'package:flutter/material.dart';

import '../models/pengguna.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';

class HomeScreen extends StatefulWidget {
  final Pengguna pengguna;
  final VoidCallback onBukaTarget;

  const HomeScreen({
    super.key,
    required this.pengguna,
    required this.onBukaTarget,
  });

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Map<String, dynamic>>> _futureTargets;

  @override
  void initState() {
    super.initState();
    _futureTargets = ApiService.instance.daftarTarget();
  }

  Future<void> _refresh() async {
    final permintaan = ApiService.instance.daftarTarget();
    if (!mounted) return;
    setState(() {
      _futureTargets = permintaan;
    });
    try {
      await permintaan;
    } catch (_) {
      // FutureBuilder menampilkan pesan kesalahan ke pengguna.
    }
  }

  int _angka(dynamic nilai) => int.tryParse('${nilai ?? 0}') ?? 0;

  @override
  Widget build(BuildContext context) {
    final kode = widget.pengguna.profilPenenun?.kodePenenun;
    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 30),
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Palugada Penenun',
                        style: TextStyle(
                          color: AppTheme.muted,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        )),
                    const SizedBox(height: 4),
                    Text('Halo, ${widget.pengguna.namaDepan}',
                        style: const TextStyle(
                          color: AppTheme.ink,
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -.4,
                        )),
                  ],
                ),
              ),
              CircleAvatar(
                radius: 25,
                backgroundColor: AppTheme.green,
                child: Text(
                  widget.pengguna.nama.trim().isEmpty
                      ? 'P'
                      : widget.pengguna.nama
                          .trim()
                          .substring(0, 1)
                          .toUpperCase(),
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 18),
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: AppTheme.green,
              borderRadius: BorderRadius.circular(26),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('PEKERJAAN PRODUKSI',
                    style: TextStyle(
                      color: Color(0xFFD8E5DF),
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 1.2,
                    )),
                const SizedBox(height: 8),
                const Text('Pantau target tenunmu di satu tempat.',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 23,
                      fontWeight: FontWeight.w800,
                      height: 1.2,
                    )),
                const SizedBox(height: 8),
                Text(
                  kode == null || kode.isEmpty
                      ? 'Lihat pekerjaan yang diberikan pengelola.'
                      : 'Kode penenun $kode • Lihat pekerjaan yang diberikan pengelola.',
                  style: const TextStyle(
                      color: Color(0xFFD8E5DF), fontSize: 14, height: 1.5),
                ),
                const SizedBox(height: 18),
                FilledButton.tonalIcon(
                  onPressed: widget.onBukaTarget,
                  icon: const Icon(Icons.assignment_outlined),
                  label: const Text('Lihat Target'),
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: AppTheme.green,
                    minimumSize: const Size(0, 46),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 26),
          const Text('Pekerjaan hari ini',
              style: TextStyle(
                color: AppTheme.ink,
                fontSize: 21,
                fontWeight: FontWeight.w800,
              )),
          const SizedBox(height: 12),
          FutureBuilder<List<Map<String, dynamic>>>(
            future: _futureTargets,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: 42),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              if (snapshot.hasError) {
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        const Text('Gagal memuat target produksi.'),
                        TextButton(
                            onPressed: _refresh,
                            child: const Text('Coba Lagi')),
                      ],
                    ),
                  ),
                );
              }
              final aktif = (snapshot.data ?? [])
                  .where((t) => '${t['status']}'.toLowerCase() == 'berjalan')
                  .toList();
              if (aktif.isEmpty) {
                return const EmptyState(
                  icon: Icons.assignment_turned_in_outlined,
                  title: 'Belum ada target produksi aktif',
                  description:
                      'Target yang diberikan Admin akan tampil di sini.',
                );
              }
              return Column(
                children: aktif.map((target) {
                  final rawProduk = target['produk'];
                  final produk =
                      rawProduk is Map ? rawProduk : <String, dynamic>{};
                  final total = _angka(target['jumlah_target']);
                  final selesai =
                      _angka(target['hasil_produksi_sum_jumlah_selesai']);
                  final sisa =
                      (total - selesai).clamp(0, total < 0 ? 0 : total);
                  final progres = total > 0
                      ? (selesai / total).clamp(0.0, 1.0).toDouble()
                      : 0.0;
                  final deadline =
                      target['tanggal_deadline']?.toString().split('T').first ??
                          '-';
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(18)),
                    child: Padding(
                      padding: const EdgeInsets.all(17),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${produk['nama_produk'] ?? 'Produk Tenun'}',
                              style: const TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w800,
                              )),
                          const SizedBox(height: 9),
                          Text(
                              'Selesai $selesai dari $total pcs • Sisa $sisa pcs',
                              style: const TextStyle(fontSize: 14)),
                          const SizedBox(height: 9),
                          LinearProgressIndicator(
                            value: progres,
                            minHeight: 9,
                            borderRadius: BorderRadius.circular(12),
                            color: AppTheme.green,
                          ),
                          const SizedBox(height: 9),
                          Text(
                              'Progres ${(progres * 100).round()}% • Tenggat $deadline',
                              style: const TextStyle(
                                  fontSize: 13, color: AppTheme.muted)),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: widget.onBukaTarget,
                              child: const Text('Lihat Detail Target'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              );
            },
          ),
        ],
      ),
    );
  }
}
