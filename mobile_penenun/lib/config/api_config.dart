class ApiConfig {
  /// Android Emulator memakai 10.0.2.2 untuk mengakses localhost laptop.
  /// Untuk HP fisik, jalankan dengan --dart-define=API_BASE_URL=http://IP_LAPTOP:8000/api/v1
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const Duration timeout = Duration(seconds: 15);
}
