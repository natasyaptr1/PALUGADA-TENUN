import '../models/target_produksi.dart';

class TargetService {
  const TargetService();

  static const bool modeDemo = true;

  Future<List<TargetProduksi>> ambilTarget() async {
    await Future<void>.delayed(const Duration(milliseconds: 350));
    return [
      TargetProduksi(
        id: 1,
        kode: 'TGT-001',
        namaProduk: 'Selendang Tenun',
        motif: 'Motif Bunga',
        jumlahTarget: 10,
        jumlahSelesai: 6,
        tenggat: DateTime(2026, 11, 5),
        selesai: false,
      ),
      TargetProduksi(
        id: 2,
        kode: 'TGT-002',
        namaProduk: 'Kain Tenun Ikat',
        motif: 'Motif Garis',
        jumlahTarget: 8,
        jumlahSelesai: 3,
        tenggat: DateTime(2026, 11, 12),
        selesai: false,
      ),
      TargetProduksi(
        id: 3,
        kode: 'TGT-003',
        namaProduk: 'Sarung Tenun',
        motif: 'Motif Klasik',
        jumlahTarget: 5,
        jumlahSelesai: 5,
        tenggat: DateTime(2026, 10, 10),
        selesai: true,
      ),
    ];
  }
}
