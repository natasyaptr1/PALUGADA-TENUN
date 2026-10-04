class Pengguna {
  final int id;
  final String nama;
  final String email;
  final String? noHp;
  final String? alamat;
  final String peran;
  final String status;
  final ProfilPenenun? profilPenenun;

  const Pengguna({
    required this.id,
    required this.nama,
    required this.email,
    required this.peran,
    required this.status,
    this.noHp,
    this.alamat,
    this.profilPenenun,
  });

  factory Pengguna.fromJson(Map<String, dynamic> json) {
    return Pengguna(
      id: (json['id'] as num).toInt(),
      nama: (json['nama'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      noHp: json['no_hp']?.toString(),
      alamat: json['alamat']?.toString(),
      peran: (json['peran'] ?? '').toString(),
      status: (json['status'] ?? '').toString(),
      profilPenenun: json['profil_penenun'] is Map<String, dynamic>
          ? ProfilPenenun.fromJson(json['profil_penenun'] as Map<String, dynamic>)
          : null,
    );
  }

  String get namaDepan {
    final bagian = nama.trim().split(RegExp(r'\s+'));
    return bagian.isEmpty || bagian.first.isEmpty ? 'Penenun' : bagian.first;
  }
}

class ProfilPenenun {
  final String kodePenenun;
  final String? keahlian;
  final String? catatan;

  const ProfilPenenun({
    required this.kodePenenun,
    this.keahlian,
    this.catatan,
  });

  factory ProfilPenenun.fromJson(Map<String, dynamic> json) {
    return ProfilPenenun(
      kodePenenun: (json['kode_penenun'] ?? '').toString(),
      keahlian: json['keahlian']?.toString(),
      catatan: json['catatan']?.toString(),
    );
  }
}
