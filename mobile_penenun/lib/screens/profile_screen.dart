import 'package:flutter/material.dart';

import '../models/pengguna.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  final Pengguna pengguna;
  const ProfileScreen({super.key, required this.pengguna});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _keluar = false;

  Future<void> _logout() async {
    setState(() => _keluar = true);
    await ApiService.instance.keluar();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.pengguna;
    final profil = p.profilPenenun;

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 30),
      children: [
        const Text(
          'Profil',
          style: TextStyle(
            color: AppTheme.ink,
            fontSize: 28,
            fontWeight: FontWeight.w800,
            letterSpacing: -.4,
          ),
        ),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: AppTheme.surface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: AppTheme.border),
          ),
          child: Column(
            children: [
              CircleAvatar(
                radius: 36,
                backgroundColor: AppTheme.green,
                child: Text(
                  p.nama.trim().isEmpty ? 'P' : p.nama.trim().substring(0, 1).toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                p.nama,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppTheme.ink,
                  fontSize: 21,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                profil?.kodePenenun.isNotEmpty == true
                    ? 'Penenun • ${profil!.kodePenenun}'
                    : 'Penenun Palugada',
                style: const TextStyle(color: AppTheme.muted, fontSize: 14),
              ),
              const SizedBox(height: 22),
              const Divider(),
              const SizedBox(height: 8),
              _InfoRow(icon: Icons.mail_outline_rounded, label: 'Email', value: p.email),
              _InfoRow(icon: Icons.phone_outlined, label: 'No. HP', value: p.noHp),
              _InfoRow(icon: Icons.location_on_outlined, label: 'Alamat', value: p.alamat),
              _InfoRow(icon: Icons.workspace_premium_outlined, label: 'Keahlian', value: profil?.keahlian),
            ],
          ),
        ),
        const SizedBox(height: 18),
        OutlinedButton.icon(
          onPressed: _keluar ? null : _logout,
          icon: const Icon(Icons.logout_rounded),
          label: Text(_keluar ? 'Keluar...' : 'Keluar dari akun'),
          style: OutlinedButton.styleFrom(
            foregroundColor: const Color(0xFF8C3D2D),
            minimumSize: const Size.fromHeight(52),
            side: const BorderSide(color: Color(0xFFE0B7AC)),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
        ),
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? value;

  const _InfoRow({required this.icon, required this.label, this.value});

  @override
  Widget build(BuildContext context) {
    final text = value == null || value!.trim().isEmpty ? '-' : value!;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: AppTheme.green, size: 22),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    color: AppTheme.muted,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  text,
                  style: const TextStyle(
                    color: AppTheme.ink,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
