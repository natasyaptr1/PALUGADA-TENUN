<div class="product-modal" id="productModal" aria-hidden="true">
    <div class="modal-backdrop js-modal-close"></div>
    <div class="product-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalProductName">
        <button class="modal-close js-modal-close" type="button" aria-label="Tutup">×</button>
        <div class="modal-image" id="modalProductImage"><div class="fabric-placeholder"><span>PB</span></div></div>
        <div class="modal-content">
            <span class="kicker" id="modalProductType">TENUN IKAT</span>
            <h2 id="modalProductName">Detail Produk</h2>
            <p id="modalProductCode" class="modal-code"></p>
            <div class="modal-price" id="modalProductPrice"></div>
            <dl class="detail-list">
                <div><dt>Motif</dt><dd id="modalMotif">-</dd></div>
                <div><dt>Warna</dt><dd id="modalColor">-</dd></div>
                <div><dt>Ukuran</dt><dd id="modalSize">-</dd></div>
                <div><dt>Bahan</dt><dd id="modalMaterial">-</dd></div>
                <div><dt>Stok</dt><dd id="modalStock">0</dd></div>
            </dl>
            @if(!auth()->check())
                <a href="{{ route('masuk') }}" class="btn btn-primary btn-block">Masuk sebagai Pembeli</a>
            @endif
        </div>
    </div>
</div>
