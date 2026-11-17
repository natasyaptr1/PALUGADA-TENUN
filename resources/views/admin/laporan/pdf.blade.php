<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $judul }}</title>
<style>
    @page { margin: 26px 28px; }
    body { font-family: DejaVu Sans, sans-serif; color: #263f34; font-size: 9px; }
    h1 { margin: 0 0 5px; color: #163f32; font-size: 20px; }
    p { margin: 0 0 12px; color: #68776f; font-size: 9px; }
    .meta { margin: 0 0 12px; color: #68776f; font-size: 8px; }
    table { width: 100%; border-collapse: collapse; }
    th { padding: 7px 6px; background: #e9f0e9; border: 1px solid #cbd8ce; color: #244b3b; text-align: left; font-size: 8px; }
    td { padding: 6px; border: 1px solid #dce4dd; vertical-align: top; font-size: 8px; }
    tr { page-break-inside: avoid; }
    .footer { margin-top: 12px; color: #829087; font-size: 8px; text-align: right; }
</style>
</head>
<body>
    <h1>{{ $judul }} - Palugada Bandar</h1>
    <p>{{ $deskripsi }}</p>
    <div class="meta">Dicetak: {{ now()->format('d/m/Y H:i') }} WIB
        @foreach ($filter as $key => $value)
            @if ($value !== null && $value !== '') &nbsp; | &nbsp; {{ str_replace('_', ' ', ucfirst($key)) }}: {{ $value }} @endif
        @endforeach
    </div>
    <table>
        <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">Tidak ada data untuk filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Palugada Bandar · Dokumen laporan internal</div>
</body>
</html>
