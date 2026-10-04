document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('productModal');
  const setText = (id, value, fallback = '-') => {
    const el = document.getElementById(id);
    if (el) el.textContent = value || fallback;
  };

  document.querySelectorAll('.js-product-detail').forEach((button) => {
    button.addEventListener('click', () => {
      if (!modal) return;
      setText('modalProductName', button.dataset.name, 'Detail Produk');
      setText('modalProductCode', button.dataset.code ? `Kode produk: ${button.dataset.code}` : '');
      setText('modalProductType', button.dataset.type || 'TENUN IKAT');
      setText('modalProductPrice', button.dataset.price, 'Rp 0');
      setText('modalMotif', button.dataset.motif);
      setText('modalColor', button.dataset.color);
      setText('modalSize', button.dataset.size);
      setText('modalMaterial', button.dataset.material);
      setText('modalStock', button.dataset.stock, '0');

      const imageWrap = document.getElementById('modalProductImage');
      if (imageWrap) {
        imageWrap.innerHTML = button.dataset.image
          ? `<img src="${button.dataset.image}" alt="${button.dataset.name || 'Produk'}">`
          : '<div class="fabric-placeholder"><span>PB</span></div>';
      }

      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    });
  });

  document.querySelectorAll('.js-modal-close').forEach((button) => {
    button.addEventListener('click', () => {
      if (!modal) return;
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    });
  });

  document.querySelectorAll('.js-coming-soon').forEach((button) => {
    button.addEventListener('click', () => {
      window.alert(button.dataset.feature || 'Fitur ini sedang disiapkan dan akan segera tersedia.');
    });
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const field = button.closest('.password-field');
      const input = field?.querySelector('[data-password-input]');
      if (!input) return;

      const willShow = input.type === 'password';
      input.type = willShow ? 'text' : 'password';
      button.classList.toggle('is-visible', willShow);
      button.setAttribute('aria-pressed', willShow ? 'true' : 'false');
      button.setAttribute('aria-label', willShow ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('open')) {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
  });
});
