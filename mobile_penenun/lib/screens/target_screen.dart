import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/empty_state.dart';

class TargetScreen extends StatelessWidget {
  const TargetScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 30),
      children: const [
        Text(
          'Target Produksi',
          style: TextStyle(
            color: AppTheme.ink,
            fontSize: 28,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        SizedBox(height: 6),
        Text(
          'Pekerjaan yang diberikan pengelola akan tersusun di sini.',
          style: TextStyle(color: AppTheme.muted, fontSize: 15, height: 1.5),
        ),
        SizedBox(height: 22),
        EmptyState(
          icon: Icons.assignment_outlined,
          title: 'Belum ada target produksi',
          description:
              'Saat pengelola memberikan target, produk, jumlah, dan batas waktunya akan tampil di halaman ini.',
        ),
      ],
    );
  }
}
