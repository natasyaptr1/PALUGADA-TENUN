class TargetProduksi {
  final int id;
  final String kode;
  final String namaProduk;
  final String motif;
  final int jumlahTarget;
  final int jumlahSelesai;
  final DateTime tenggat;
  final bool selesai;

  const TargetProduksi({
    required this.id,
    required this.kode,
    required this.namaProduk,
    required this.motif,
    required this.jumlahTarget,
    required this.jumlahSelesai,
    required this.tenggat,
    required this.selesai,
  });

  int get sisa => (jumlahTarget - jumlahSelesai).clamp(0, jumlahTarget).toInt();
  double get progres => jumlahTarget <= 0
      ? 0
      : (jumlahSelesai / jumlahTarget).clamp(0.0, 1.0).toDouble();
}
