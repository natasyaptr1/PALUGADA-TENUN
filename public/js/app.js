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

      let images = [];
      try {
        images = JSON.parse(button.dataset.images || '[]');
      } catch (e) {
        images = [];
      }
      if (images.length === 0 && button.dataset.image) {
        images = [button.dataset.image];
      }

      const imageWrap = document.getElementById('modalProductImage');
      const thumbsWrap = document.getElementById('modalThumbnails');

      if (imageWrap) {
        imageWrap.innerHTML = images.length > 0
          ? `<img id="modalMainImg" src="${images[0]}" alt="${button.dataset.name || 'Produk'}">`
          : '<div class="fabric-placeholder"><span>PB</span></div>';
      }

      if (thumbsWrap) {
        thumbsWrap.innerHTML = '';
        if (images.length > 1) {
          images.forEach((src, idx) => {
            const thumbBtn = document.createElement('button');
            thumbBtn.type = 'button';
            thumbBtn.className = `modal-thumb-btn ${idx === 0 ? 'active' : ''}`;
            thumbBtn.setAttribute('aria-label', `Lihat foto ke-${idx + 1}`);
            thumbBtn.innerHTML = `<img src="${src}" alt="Thumbnail ${idx + 1}">`;
            thumbBtn.addEventListener('click', () => {
              const mainImg = document.getElementById('modalMainImg');
              if (mainImg) mainImg.src = src;
              thumbsWrap.querySelectorAll('.modal-thumb-btn').forEach((b) => b.classList.remove('active'));
              thumbBtn.classList.add('active');
            });
            thumbsWrap.appendChild(thumbBtn);
          });
        }
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

  document.querySelectorAll('.js-btn-reset').forEach((button) => {
    button.addEventListener('click', (event) => {
      const form = button.closest('form');
      if (!form) return;
      const mode = button.dataset.mode;
      if (mode === 'edit') {
        form.reset();
      } else {
        event.preventDefault();
        form.querySelectorAll('input, textarea, select').forEach((field) => {
          if (field.type === 'hidden') return;
          if (field.readOnly) return;
          if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
          } else if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
          } else {
            field.value = '';
          }
        });
      }
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
