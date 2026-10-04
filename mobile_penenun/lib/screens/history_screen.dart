import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';

class HistoryScreen extends StatelessWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 30),
      children: const [
        Text(
          'Riwayat Produksi',
          style: TextStyle(
            color: AppTheme.ink,
            fontSize: 28,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        SizedBox(height: 6),
        Text(
          'Hasil produksi yang sudah dicatat akan tersimpan di sini.',
          style: TextStyle(color: AppTheme.muted, fontSize: 15, height: 1.5),
        ),
        SizedBox(height: 22),
        EmptyState(
          icon: Icons.history_rounded,
          title: 'Belum ada riwayat produksi',
          description:
              'Setelah hasil produksi dicatat, riwayat pekerjaanmu akan tampil secara otomatis.',
        ),
      ],
    );
  }
}
