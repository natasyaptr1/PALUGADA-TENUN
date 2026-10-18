import 'dart:math';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../models/pengguna.dart';
import 'api_exception.dart';

class ApiService {
  ApiService._();
  static final ApiService instance = ApiService._();

  static const _tokenKey = 'palugada_penenun_token';
  static const FlutterSecureStorage _storage = FlutterSecureStorage();

  Map<String, String> _headers({String? token}) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<bool> cekKoneksi() async {
    try {
      final response = await http
          .get(
            Uri.parse('${ApiConfig.baseUrl}/status'),
            headers: const {'Accept': 'application/json'},
          )
          .timeout(ApiConfig.timeout);
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<Pengguna> masuk({
    required String email,
    required String kataSandi,
  }) async {
    if (email.trim().isEmpty || kataSandi.isEmpty) {
      throw const ApiException('Email dan kata sandi wajib diisi.');
    }

    try {
      final response = await http
          .post(
            Uri.parse('${ApiConfig.baseUrl}/autentikasi/masuk'),
            headers: _headers(),
            body: jsonEncode({
              'email': email.trim(),
              'kata_sandi': kataSandi,
              'nama_perangkat': 'android-penenun',
            }),
          )
          .timeout(ApiConfig.timeout);

      final data = _decode(response.body);
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw ApiException(_pesanDari(data, 'Email atau kata sandi tidak sesuai.'));
      }

      final token = data['token']?.toString();
      final rawPengguna = data['pengguna'];
      if (token == null || rawPengguna is! Map<String, dynamic>) {
        throw const ApiException('Respons server tidak lengkap. Silakan coba lagi.');
      }

      final pengguna = Pengguna.fromJson(rawPengguna);
      if (pengguna.peran != 'penenun') {
        await _hapusTokenDiServer(token);
        throw const ApiException(
          'Akun ini tidak memiliki akses ke aplikasi penenun.',
        );
      }

      if (pengguna.status != 'aktif') {
        await _hapusTokenDiServer(token);
        throw const ApiException(
          'Akun penenun sedang tidak aktif. Hubungi pengelola Palugada.',
        );
      }

      await _storage.write(key: _tokenKey, value: token);
      return await saya() ?? pengguna;
    } on ApiException {
      rethrow;
    } on SocketException {
      throw const ApiException(
        'Tidak dapat terhubung ke Palugada. Pastikan server aktif dan perangkat terhubung ke jaringan yang sama.',
      );
    } on HttpException {
      throw const ApiException('Terjadi gangguan saat menghubungi Palugada.');
    } on FormatException {
      throw const ApiException('Respons dari server tidak dapat dibaca.');
    } catch (_) {
      throw const ApiException('Tidak dapat masuk. Silakan coba lagi.');
    }
  }

  Future<Pengguna?> saya() async {
    final token = await tokenTersimpan();
    if (token == null || token.isEmpty) return null;

    try {
      final response = await http
          .get(
            Uri.parse('${ApiConfig.baseUrl}/autentikasi/saya'),
            headers: _headers(token: token),
          )
          .timeout(ApiConfig.timeout);

      if (response.statusCode == 401) {
        await _storage.delete(key: _tokenKey);
        return null;
      }

      if (response.statusCode != 200) return null;
      final data = _decode(response.body);
      final raw = data['data'];
      if (raw is! Map<String, dynamic>) return null;

      final pengguna = Pengguna.fromJson(raw);
      if (pengguna.peran != 'penenun' || pengguna.status != 'aktif') {
        await keluar();
        return null;
      }
      return pengguna;
    } catch (_) {
      return null;
    }
  }

  Future<List<Map<String, dynamic>>> daftarTarget() async => _daftar('penenun/target');

  Future<List<Map<String, dynamic>>> daftarHasil() async => _daftar('penenun/riwayat');

  Future<List<Map<String, dynamic>>> _daftar(String endpoint) async {
    final token = await tokenTersimpan();
    if (token == null) throw const ApiException('Silakan masuk kembali.');
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/$endpoint'),
        headers: _headers(token: token),
      ).timeout(ApiConfig.timeout);
      final decoded = _decode(response.body);
      if (response.statusCode != 200) {
        throw ApiException(_pesanDari(decoded, 'Gagal memuat data produksi.'));
      }
      final raw = decoded['data'];
      if (raw is! List) return [];
      return raw.whereType<Map<String, dynamic>>().toList();
    } on ApiException {
      rethrow;
    } catch (_) {
      throw const ApiException('Tidak dapat memuat data. Periksa koneksi server.');
    }
  }

  Future<void> catatHasil({required int targetId, required int jumlah, String? catatan, String? requestId}) async {
    final token = await tokenTersimpan();
    if (token == null) throw const ApiException('Silakan masuk kembali.');
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/penenun/target/$targetId/hasil'),
        headers: _headers(token: token),
        body: jsonEncode({'jumlah_selesai': jumlah, 'catatan': catatan, 'request_id': requestId ?? '${DateTime.now().microsecondsSinceEpoch}-${Random.secure().nextInt(1 << 32)}'}),
      ).timeout(ApiConfig.timeout);
      final decoded = _decode(response.body);
      if (response.statusCode != 201) {
        throw ApiException(_pesanDari(decoded, 'Gagal menyimpan hasil produksi.'));
      }
    } on ApiException {
      rethrow;
    } catch (_) {
      throw const ApiException('Gagal mengirim hasil produksi. Periksa koneksi server.');
    }
  }

  Future<String?> tokenTersimpan() => _storage.read(key: _tokenKey);

  Future<void> keluar() async {
    final token = await tokenTersimpan();
    if (token != null && token.isNotEmpty) {
      await _hapusTokenDiServer(token);
    }
    await _storage.delete(key: _tokenKey);
  }

  Future<void> _hapusTokenDiServer(String token) async {
    try {
      await http
          .post(
            Uri.parse('${ApiConfig.baseUrl}/autentikasi/keluar'),
            headers: _headers(token: token),
          )
          .timeout(ApiConfig.timeout);
    } catch (_) {
      // Token lokal tetap dibersihkan walaupun server sedang tidak dapat dijangkau.
    }
  }

  Map<String, dynamic> _decode(String body) {
    if (body.trim().isEmpty) return <String, dynamic>{};
    final decoded = jsonDecode(body);
    return decoded is Map<String, dynamic> ? decoded : <String, dynamic>{};
  }

  String _pesanDari(Map<String, dynamic> data, String fallback) {
    final pesan = data['pesan']?.toString();
    if (pesan != null && pesan.trim().isNotEmpty) return pesan;

    final errors = data['errors'];
    if (errors is Map<String, dynamic>) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) return value.first.toString();
      }
    }
    return fallback;
  }
}
