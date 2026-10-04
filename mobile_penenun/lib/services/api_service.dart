import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class ApiService {
  static const _tokenKey = 'token_palugada';

  Future<Map<String, dynamic>> masuk({required String email, required String kataSandi}) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/autentikasi/masuk'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'kata_sandi': kataSandi,
        'nama_perangkat': 'android-penenun',
      }),
    );

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw Exception(data['pesan'] ?? 'Tidak dapat masuk. Coba lagi.');
    }

    final pengguna = data['pengguna'] as Map<String, dynamic>?;
    if (pengguna?['peran'] != 'penenun') {
      throw Exception('Akun ini bukan akun penenun.');
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, data['token'] as String);
    return pengguna!;
  }

  Future<Map<String, dynamic>?> saya() async {
    final token = await tokenTersimpan();
    if (token == null) return null;

    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/autentikasi/saya'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) return null;
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return data['data'] as Map<String, dynamic>?;
  }

  Future<String?> tokenTersimpan() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> keluar() async {
    final token = await tokenTersimpan();
    if (token != null) {
      await http.post(
        Uri.parse('${ApiConfig.baseUrl}/autentikasi/keluar'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }
}
