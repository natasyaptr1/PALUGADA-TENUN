import 'package:flutter/material.dart';

import 'screens/splash_screen.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const PalugadaPenenunApp());
}

class PalugadaPenenunApp extends StatelessWidget {
  const PalugadaPenenunApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Palugada Penenun',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      home: const SplashScreen(),
    );
  }
}
