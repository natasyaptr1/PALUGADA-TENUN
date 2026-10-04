param(
    [string]$BaseUrl = "http://127.0.0.1:8000/api/v1"
)

Write-Host "Menguji API Palugada: $BaseUrl/status" -ForegroundColor Cyan
try {
    $hasil = Invoke-RestMethod -Uri "$BaseUrl/status" -Method Get -Headers @{ Accept = "application/json" }
    $hasil | ConvertTo-Json -Depth 5
    Write-Host "API dapat diakses." -ForegroundColor Green
} catch {
    Write-Host "API belum dapat diakses." -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}
