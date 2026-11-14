@extends('layouts.app')
@section('title','Pesanan Saya - Palugada Bandar')
@section('content')
<section class="member-simple-page member-list-page-v4">
    <div class="member-simple-head member-simple-head-v4">
        <div>
            <span class="eyebrow">PESANAN</span>
            <h1>Pesanan Saya</h1>
            <p>Pantau status dan riwayat pembelianmu di sini.</p>
        </div>
        <a href="{{ route('pembeli.katalog') }}" class="btn btn-outline">Lihat Koleksi</a>
    </div>

    @if($daftarPesanan->isEmpty())
        <div class="member-flat-empty member-flat-empty-large member-empty-v4">
            <span>◇</span>
            <div>
                <strong>Belum ada pesanan</strong>
                <p>Setelah kamu melakukan pembelian, pesanan akan tampil di halaman ini.</p>
            </div>
            <a href="{{ route('pembeli.katalog') }}" class="btn btn-primary">Mulai Belanja</a>
        </div>
    @else
        <div class="order-list clean-order-list-page">
            @foreach($daftarPesanan as $pesanan)
                @php
                    $bayar = $pesanan->pembayaran->first();
                    $bisaBayarMidtrans = $pesanan->status_pesanan === 'menunggu_pembayaran' && $bayar && $bayar->metode === 'midtrans';
                @endphp
                <article class="order-row-clean" style="align-items: center;">
                    <div class="order-main"><small>Kode Pesanan</small><strong>{{ $pesanan->kode_pesanan }}</strong></div>
                    <div><small>Tanggal</small><strong>{{ optional($pesanan->tanggal_pesanan)->format('d M Y') }}</strong></div>
                    <div><small>Status</small><strong>{{ ucfirst(str_replace('_',' ',$pesanan->status_pesanan)) }}</strong></div>
                    <div><small>Total</small><strong>Rp {{ number_format($pesanan->total,0,',','.') }}</strong></div>
                    @if($bisaBayarMidtrans)
                        <div style="text-align: right; display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn btn-outline btn-sm btn-cek-midtrans" data-url="{{ route('pembeli.pesanan.cek-status', $pesanan) }}" style="padding: 7px 12px; font-size: 13px;">
                                Cek Status
                            </button>
                            <button type="button" class="btn btn-primary btn-sm btn-bayar-midtrans" data-url="{{ route('pembeli.pesanan.bayar', $pesanan) }}" data-cek-url="{{ route('pembeli.pesanan.cek-status', $pesanan) }}" style="padding: 7px 14px; font-size: 13px;">
                                Bayar Sekarang
                            </button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $daftarPesanan->links() }}</div>
    @endif
</section>
@endsection

@push('scripts')
<script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
async function cekStatusMidtrans(url, btn = null) {
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Memeriksa...';
    }
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await res.json();
        if (data.status === 'dibayar') {
            alert('Pembayaran berhasil dikonfirmasi! Status pesanan berubah menjadi Dibayar.');
            window.location.reload();
        } else {
            alert(data.message || 'Pembayaran belum diselesaikan di Midtrans.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Cek Status';
            }
        }
    } catch (e) {
        alert('Gagal memeriksa status pembayaran.');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Cek Status';
        }
    }
}

document.querySelectorAll('.btn-cek-midtrans').forEach(btn => {
    btn.addEventListener('click', function() {
        cekStatusMidtrans(this.dataset.url, this);
    });
});

document.querySelectorAll('.btn-bayar-midtrans').forEach(btn => {
    btn.addEventListener('click', async function() {
        const url = this.dataset.url;
        const cekUrl = this.dataset.cekUrl;
        const defaultText = this.textContent;
        this.disabled = true;
        this.textContent = 'Memuat Midtrans...';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.snap_token) {
                window.snap.pay(data.snap_token, {
                    onSuccess: function(result) {
                        cekStatusMidtrans(cekUrl);
                    },
                    onPending: function(result) {
                        alert('Silakan selesaikan pembayaran sesuai instruksi.');
                        cekStatusMidtrans(cekUrl);
                    },
                    onError: function(result) {
                        alert('Pembayaran gagal atau dibatalkan.');
                        window.location.reload();
                    },
                    onClose: function() {
                        btn.disabled = false;
                        btn.textContent = defaultText;
                        // Otomatis cek status setelah modal ditutup
                        cekStatusMidtrans(cekUrl);
                    }
                });
            } else {
                alert(data.message || 'Gagal memuat Snap Token.');
                btn.disabled = false;
                btn.textContent = defaultText;
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi.');
            btn.disabled = false;
            btn.textContent = defaultText;
        }
    });
});
</script>
@endpush
