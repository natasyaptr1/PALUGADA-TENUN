import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class HomeScreen extends StatelessWidget {
  final Map<String, dynamic> pengguna;
  const HomeScreen({super.key, required this.pengguna});

  @override
  Widget build(BuildContext context) {
    final nama = (pengguna['nama'] ?? 'Penenun').toString();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Palugada Penenun'),
        backgroundColor: AppTheme.cream,
        actions: [IconButton(tooltip: 'Keluar', onPressed: () async {
          await ApiService().keluar();
          if (!context.mounted) return;
          Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
        }, icon: const Icon(Icons.logout_rounded))],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(color: AppTheme.green, borderRadius: BorderRadius.circular(24)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('SELAMAT BEKERJA', style: TextStyle(color: Color(0xFFD7E2DC), fontSize: 11, letterSpacing: 1.4, fontWeight: FontWeight.w700)),
              const SizedBox(height: 8),
              Text('Halo, ${nama.split(' ').first}', style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              const Text('Semua pekerjaan produksi nantinya bisa dilihat dari satu aplikasi.', style: TextStyle(color: Color(0xFFD7E2DC), height: 1.45)),
            ]),
          ),
          const SizedBox(height: 22),
          const Text('Menu utama', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppTheme.ink)),
          const SizedBox(height: 12),
          const _MenuCard(icon: Icons.assignment_outlined, title: 'Target Produksi', subtitle: 'Lihat produk, jumlah target, dan deadline.', note: 'Siap ditambahkan pada pengembangan berikutnya'),
          const _MenuCard(icon: Icons.add_task_rounded, title: 'Catat Hasil Produksi', subtitle: 'Masukkan jumlah produk yang sudah selesai.', note: 'Siap ditambahkan pada pengembangan berikutnya'),
          const _MenuCard(icon: Icons.history_rounded, title: 'Riwayat Produksi', subtitle: 'Lihat pekerjaan yang pernah dicatat.', note: 'Siap ditambahkan pada pengembangan berikutnya'),
        ],
      ),
    );
  }
}

class _MenuCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final String note;
  const _MenuCard({required this.icon, required this.title, required this.subtitle, required this.note});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(width: 44, height: 44, decoration: BoxDecoration(color: AppTheme.greenSoft, borderRadius: BorderRadius.circular(14)), child: Icon(icon, color: AppTheme.green)),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, color: AppTheme.ink)),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(fontSize: 12, color: AppTheme.muted, height: 1.45)),
            const SizedBox(height: 8),
            Text(note, style: const TextStyle(fontSize: 10, color: AppTheme.green, fontWeight: FontWeight.w700)),
          ])),
        ]),
      ),
    );
  }
}
