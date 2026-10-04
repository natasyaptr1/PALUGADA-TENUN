import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final pengguna = await ApiService().saya();
  runApp(PalugadaPenenunApp(penggunaAwal: pengguna));
}

class PalugadaPenenunApp extends StatelessWidget {
  final Map<String, dynamic>? penggunaAwal;
  const PalugadaPenenunApp({super.key, this.penggunaAwal});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Palugada Penenun',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      home: penggunaAwal?['peran'] == 'penenun' ? HomeScreen(pengguna: penggunaAwal!) : const LoginScreen(),
    );
  }
}
