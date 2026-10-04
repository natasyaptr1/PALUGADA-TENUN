import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _api = ApiService();
  bool _loading = false;
  String? _error;

  Future<void> _masuk() async {
    setState(() { _loading = true; _error = null; });
    try {
      final pengguna = await _api.masuk(email: _email.text.trim(), kataSandi: _password.text);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => HomeScreen(pengguna: pengguna)));
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(width: 54, height: 54, decoration: BoxDecoration(color: AppTheme.green, borderRadius: BorderRadius.circular(16)), alignment: Alignment.center, child: const Text('PB', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800))),
                const SizedBox(height: 28),
                const Text('Palugada Penenun', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w700, color: AppTheme.ink)),
                const SizedBox(height: 8),
                const Text('Masuk untuk melihat pekerjaan dan mencatat hasil produksi.', style: TextStyle(color: AppTheme.muted, height: 1.5)),
                const SizedBox(height: 28),
                Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(children: [
                  TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email')),
                  const SizedBox(height: 14),
                  TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Kata sandi')),
                  if (_error != null) ...[const SizedBox(height: 12), Align(alignment: Alignment.centerLeft, child: Text(_error!, style: const TextStyle(color: Colors.redAccent, fontSize: 12)))],
                  const SizedBox(height: 18),
                  ElevatedButton(onPressed: _loading ? null : _masuk, child: Text(_loading ? 'Memeriksa akun...' : 'Masuk')),
                ]))),
                const SizedBox(height: 18),
                const Text('Gunakan akun penenun yang dibuat oleh Admin Palugada.', style: TextStyle(fontSize: 12, color: AppTheme.muted)),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
