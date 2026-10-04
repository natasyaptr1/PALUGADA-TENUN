import 'package:flutter/material.dart';

import '../models/pengguna.dart';
import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';

class HomeScreen extends StatelessWidget {
  final Pengguna pengguna;
  final VoidCallback onBukaTarget;

  const HomeScreen({
    super.key,
    required this.pengguna,
    required this.onBukaTarget,
  });

  @override
  Widget build(BuildContext context) {
    final kode = pengguna.profilPenenun?.kodePenenun;

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 30),
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Palugada Penenun',
                    style: TextStyle(
                      color: AppTheme.muted,
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Halo, ${pengguna.namaDepan}',
                    style: const TextStyle(
                      color: AppTheme.ink,
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -.4,
                    ),
                  ),
                ],
              ),
            ),
            CircleAvatar(
              radius: 25,
              backgroundColor: AppTheme.green,
              child: Text(
                pengguna.nama.trim().isEmpty
                    ? 'P'
                    : pengguna.nama.trim().substring(0, 1).toUpperCase(),
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 18,
                ),
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
              const Text(
                'PEKERJAAN PRODUKSI',
                style: TextStyle(
                  color: Color(0xFFD8E5DF),
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.2,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Semua targetmu akan tampil di satu tempat.',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  height: 1.2,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                kode == null || kode.isEmpty
                    ? 'Buka menu Target untuk melihat pekerjaan yang diberikan pengelola.'
                    : 'Kode penenun $kode • Buka menu Target untuk melihat pekerjaanmu.',
                style: const TextStyle(
                  color: Color(0xFFD8E5DF),
                  fontSize: 14,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 18),
              FilledButton.tonalIcon(
                onPressed: onBukaTarget,
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
        const Text(
          'Pekerjaan hari ini',
          style: TextStyle(
            color: AppTheme.ink,
            fontSize: 21,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 12),
        const EmptyState(
          icon: Icons.assignment_turned_in_outlined,
          title: 'Belum ada target produksi',
          description:
              'Target yang diberikan oleh pengelola akan muncul di sini setelah modul produksi mulai digunakan.',
        ),
      ],
    );
  }
}
