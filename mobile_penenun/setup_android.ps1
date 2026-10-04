$ErrorActionPreference = "Stop"

Write-Host "== Palugada Penenun - Setup Android ==" -ForegroundColor Green

if (-not (Get-Command flutter -ErrorAction SilentlyContinue)) {
    Write-Host "Flutter belum ditemukan di PATH." -ForegroundColor Red
    Write-Host "Install Flutter terlebih dahulu, lalu buka terminal baru dan jalankan: flutter doctor"
    exit 1
}

flutter doctor

if (-not (Test-Path (Join-Path $PSScriptRoot "android"))) {
    Write-Host "`nMembuat folder Android..." -ForegroundColor Cyan
    flutter create . --platforms=android --project-name palugada_penenun --org com.palugada
} else {
    Write-Host "`nFolder Android sudah ada, tidak dibuat ulang." -ForegroundColor Yellow
}

$manifest = Join-Path $PSScriptRoot "android\app\src\main\AndroidManifest.xml"
if (Test-Path $manifest) {
    $content = Get-Content $manifest -Raw

    $manifestOpen = '<manifest xmlns:android="http://schemas.android.com/apk/res/android">'
    if ($content -notmatch 'android.permission.INTERNET') {
        $replacement = $manifestOpen + "`r`n    <uses-permission android:name=`"android.permission.INTERNET`" />"
        $content = $content.Replace($manifestOpen, $replacement)
    }

    if ($content -notmatch 'usesCleartextTraffic') {
        $content = $content.Replace('<application', '<application android:usesCleartextTraffic="true"')
    }

    Set-Content -Path $manifest -Value $content -Encoding UTF8
    Write-Host "AndroidManifest disiapkan untuk pengujian API lokal." -ForegroundColor Green
}

flutter pub get

Write-Host "`nSetup selesai." -ForegroundColor Green
Write-Host "Emulator Android : flutter run"
Write-Host "HP fisik        : flutter run --dart-define=API_BASE_URL=http://IP_LAPTOP:8000/api/v1"
