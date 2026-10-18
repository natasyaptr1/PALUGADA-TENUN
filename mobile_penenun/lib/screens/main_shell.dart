import 'package:flutter/material.dart';

import '../models/pengguna.dart';
import 'history_screen.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'target_screen.dart';

class MainShell extends StatefulWidget {
  final Pengguna pengguna;
  const MainShell({super.key, required this.pengguna});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  int _versiBeranda = 0;
  int _versiRiwayat = 0;

  void _pilih(int value) {
    setState(() {
      // Muat ulang data Beranda/Riwayat ketika tab tersebut dibuka.
      if (value == 0 && _index != 0) _versiBeranda++;
      if (value == 2 && _index != 2) _versiRiwayat++;
      _index = value;
    });
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(
        key: ValueKey('beranda-$_versiBeranda'),
        pengguna: widget.pengguna,
        onBukaTarget: () => _pilih(1),
      ),
      const TargetScreen(),
      HistoryScreen(key: ValueKey('riwayat-$_versiRiwayat')),
      ProfileScreen(pengguna: widget.pengguna),
    ];

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: IndexedStack(index: _index, children: pages),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _pilih,
        height: 74,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment_rounded),
            label: 'Target',
          ),
          NavigationDestination(
            icon: Icon(Icons.history_outlined),
            selectedIcon: Icon(Icons.history_rounded),
            label: 'Riwayat',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
