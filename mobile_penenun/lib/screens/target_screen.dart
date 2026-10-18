import 'package:flutter/material.dart';
import '../services/api_service.dart';

class TargetScreen extends StatefulWidget {
  const TargetScreen({super.key});

  @override
  State<TargetScreen> createState() => _TargetScreenState();
}

class _TargetScreenState extends State<TargetScreen> {
  late Future<List<Map<String, dynamic>>> _future;
  bool _mengirim = false;
  bool _selesai = false;

  @override
  void initState() {
    super.initState();
    _future = ApiService.instance.daftarTarget();
  }

  void _muat() {
    if (!mounted) return;
    setState(() {
      _future = ApiService.instance.daftarTarget();
    });
  }

  Future<void> _refresh() async {
    _muat();
    try {
      await _future;
    } catch (_) {
      // Error ditampilkan oleh FutureBuilder.
    }
  }

  Future<void> _isiHasil(Map<String, dynamic> target) async {
    if (_mengirim) return;

    final total = int.tryParse('${target['jumlah_target']}') ?? 0;
    final sudah = int.tryParse(
          '${target['hasil_produksi_sum_jumlah_selesai'] ?? 0}',
        ) ??
        0;
    final sisa = total - sudah;
    final targetId = int.tryParse('${target['id']}');

    if (sisa <= 0 || targetId == null) return;

    final formKey = GlobalKey<FormState>();
    final jumlah = TextEditingController();
    final catatan = TextEditingController();

    final kirim = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Input Hasil Produksi'),
        content: SingleChildScrollView(
          child: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Sisa target: $sisa pcs'),
                const SizedBox(height: 12),
                TextFormField(
                  controller: jumlah,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Jumlah selesai',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    final angka = int.tryParse((value ?? '').trim());
                    if (angka == null || angka < 1 || angka > sisa) {
                      return 'Isi jumlah 1 sampai $sisa';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: catatan,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Catatan (opsional)',
                    border: OutlineInputBorder(),
                  ),
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.pop(dialogContext, true);
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );

    if (!mounted) {
      jumlah.dispose();
      catatan.dispose();
      return;
    }
    if (kirim != true) {
      jumlah.dispose();
      catatan.dispose();
      return;
    }

    final angkaHasil = int.parse(jumlah.text.trim());
    final teksCatatan = catatan.text.trim();
    setState(() {
      _mengirim = true;
    });

    try {
      await ApiService.instance.catatHasil(
        targetId: targetId,
        jumlah: angkaHasil,
        catatan: teksCatatan,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Hasil produksi berhasil dicatat.')),
      );
      _muat();
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal mencatat hasil: $error')),
      );
    } finally {
      jumlah.dispose();
      catatan.dispose();
      if (mounted) {
        setState(() {
          _mengirim = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return ListView(
                children: [
                  const SizedBox(height: 100),
                  const Center(child: Text('Gagal memuat target produksi.')),
                  Center(
                    child: TextButton(
                      onPressed: _muat,
                      child: const Text('Coba Lagi'),
                    ),
                  ),
                ],
              );
            }

            final data = snapshot.data ?? <Map<String, dynamic>>[];
            final targetTampil = data.where((target) {
              return _selesai
                  ? target['status'] == 'selesai'
                  : target['status'] != 'selesai' &&
                      target['status'] != 'dibatalkan';
            }).toList();

            return ListView(
              padding: const EdgeInsets.all(18),
              children: [
                const Text(
                  'Target Produksi',
                  style: TextStyle(fontSize: 27, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Lihat target aktif dan hasil penyelesaian pekerjaan tenun.',
                  style: TextStyle(fontSize: 15, color: Color(0xFF68776F)),
                ),
                const SizedBox(height: 16),
                SegmentedButton<bool>(
                  segments: const [
                    ButtonSegment(value: false, label: Text('Aktif')),
                    ButtonSegment(value: true, label: Text('Selesai')),
                  ],
                  selected: {_selesai},
                  onSelectionChanged: (value) {
                    setState(() {
                      _selesai = value.first;
                    });
                  },
                ),
                const SizedBox(height: 15),
                if (targetTampil.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(24),
                    child: Center(
                      child: Text('Belum ada target pada kategori ini.'),
                    ),
                  ),
                for (final target in targetTampil) _kartuTarget(target),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _kartuTarget(Map<String, dynamic> target) {
    final produk = target['produk'] is Map ? target['produk'] as Map : {};
    final total = int.tryParse('${target['jumlah_target']}') ?? 0;
    final selesai = int.tryParse(
          '${target['hasil_produksi_sum_jumlah_selesai'] ?? 0}',
        ) ??
        0;
    final sisa = total - selesai;
    final persen = total > 0 ? (selesai / total).clamp(0.0, 1.0).toDouble() : 0.0;
    final tenggat = target['tanggal_deadline']?.toString().split('T').first ?? '-';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${produk['nama_produk'] ?? 'Produk'}',
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text('Jumlah selesai: $selesai dari $total · Sisa: $sisa'),
            const SizedBox(height: 4),
            Text('Status: ${target['status']}'),
            Text('Tenggat: $tenggat'),
            const SizedBox(height: 12),
            LinearProgressIndicator(value: persen, minHeight: 8),
            const SizedBox(height: 5),
            Text('${(persen * 100).round()}% selesai'),
            if (target['status'] == 'berjalan' && sisa > 0)
              Align(
                alignment: Alignment.centerRight,
                child: FilledButton(
                  onPressed: _mengirim ? null : () => _isiHasil(target),
                  child: const Text('Catat Hasil'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
