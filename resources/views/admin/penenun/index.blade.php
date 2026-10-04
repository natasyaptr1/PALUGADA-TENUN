@extends('layouts.app')
@section('title','Data Penenun')
@section('content')
<div class="page-head"><div><span class="eyebrow">PENGELOLAAN PENENUN</span><h1>Data Penenun</h1><p>Kelola akun penenun yang nantinya digunakan untuk melihat target dan mencatat hasil produksi dari aplikasi Android.</p></div><a class="btn btn-primary" href="{{ route('admin.penenun.create') }}">Tambah Penenun</a></div>
<form class="toolbar"><input name="cari" value="{{ request('cari') }}" placeholder="Cari nama, email, atau kode..."><button class="btn btn-secondary">Cari</button></form>
<div class="table-card"><table><thead><tr><th>Kode</th><th>Penenun</th><th>Kontak</th><th>Keahlian</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($daftarPenenun as $penenun)<tr><td><span class="badge">{{ $penenun->profilPenenun?->kode_penenun }}</span></td><td><strong>{{ $penenun->nama }}</strong><br><small>{{ $penenun->email }}</small></td><td>{{ $penenun->no_hp ?: '-' }}</td><td>{{ $penenun->profilPenenun?->keahlian ?: '-' }}</td><td>{{ ucfirst($penenun->status) }}</td><td class="actions"><a href="{{ route('admin.penenun.edit',$penenun) }}">Ubah</a><form method="POST" action="{{ route('admin.penenun.destroy',$penenun) }}">@csrf @method('DELETE')<button onclick="return confirm('Hapus penenun ini?')">Hapus</button></form></td></tr>@empty<tr><td colspan="6" class="empty">Belum ada penenun.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $daftarPenenun->links() }}</div>
@endsection
