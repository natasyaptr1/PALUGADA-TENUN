/**
 * Integrasi Logika Ongkos Kirim dan Pembayaran pada Halaman Checkout PALUGADA-TENUN
 */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-checkout');
    if (!form) return;

    // 1. Ambil data konfigurasi dari atribut data-* form
    const urlTujuan = form.dataset.urlTujuan;
    const urlOpsi = form.dataset.urlOpsi;
    const subtotal = parseFloat(form.dataset.subtotal || '0');
    const pembayaranPerMetode = JSON.parse(form.dataset.pembayaranPerMetode || '{}');

    const oldMetodePengiriman = form.dataset.oldMetodePengiriman || 'ambil_di_tempat';
    const oldMetodePembayaran = form.dataset.oldMetodePembayaran || '';
    const oldTujuanId = form.dataset.oldTujuanId || '';
    const oldTujuanLabel = form.dataset.oldTujuanLabel || '';
    const oldKurirKode = form.dataset.oldKurirKode || '';
    const oldLayananKurir = form.dataset.oldLayananKurir || '';

    // 2. Elemen DOM
    const radioMetodePengiriman = form.querySelectorAll('input[name="metode_pengiriman"]');
    const blokEkspedisi = document.getElementById('blok-ekspedisi');
    const blokAlamatPengiriman = document.getElementById('blok-alamat-pengiriman');
    const inputAlamatPengiriman = document.getElementById('alamat_pengiriman');

    const inputTujuanId = document.getElementById('tujuan_id');
    const inputTujuanLabel = document.getElementById('tujuan_label');
    const inputKurirKode = document.getElementById('kurir_kode');
    const inputLayananKurir = document.getElementById('layanan_kurir');

    const inputCariTujuan = document.getElementById('input-cari-tujuan');
    const wadahCariTujuan = document.getElementById('wadah-cari-tujuan');
    const dropdownHasilTujuan = document.getElementById('dropdown-hasil-tujuan');
    const loadingTujuan = document.getElementById('loading-tujuan');

    const wadahTujuanTerpilih = document.getElementById('wadah-tujuan-terpilih');
    const teksTujuanTerpilih = document.getElementById('teks-tujuan-terpilih');
    const btnGantiTujuan = document.getElementById('btn-ganti-tujuan');

    const wadahOpsiKurir = document.getElementById('wadah-opsi-kurir');
    const loadingKurir = document.getElementById('loading-kurir');
    const pesanErrorKurir = document.getElementById('pesan-error-kurir');
    const pesanKosongKurir = document.getElementById('pesan-kosong-kurir');
    const daftarOpsiKurir = document.getElementById('daftar-opsi-kurir');

    const radioMetodePembayaran = form.querySelectorAll('input[name="metode"]');
    const teksRingkasanOngkir = document.getElementById('ringkasan-ongkir');
    const teksRingkasanTotal = document.getElementById('ringkasan-total');
    const btnSubmit = document.getElementById('btn-submit-pesanan');

    let controllerCariTujuan = null;
    let timeoutDebounce = null;
    let ongkirTerpilih = 0;
    let opsiTersediaSaatIni = [];

    // Helper format rupiah
    function formatRupiah(angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(angka));
    }

    // 3. Kelola Perubahan Metode Pengiriman (Ambil di Tempat vs Ekspedisi)
    function perbaruiMetodePengiriman(metode) {
        // Update styling radio card
        radioMetodePengiriman.forEach(function (radio) {
            const card = radio.closest('.ongkir-radio-card');
            if (card) {
                if (radio.value === metode) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            }
        });

        if (metode === 'ekspedisi') {
            blokEkspedisi.style.display = 'block';
            blokAlamatPengiriman.style.display = 'block';
            inputAlamatPengiriman.setAttribute('required', 'required');

            // Periksa apakah kurir sudah dipilih
            if (inputKurirKode.value && inputLayananKurir.value && ongkirTerpilih > 0) {
                teksRingkasanOngkir.textContent = formatRupiah(ongkirTerpilih);
                teksRingkasanTotal.textContent = formatRupiah(subtotal + ongkirTerpilih);
                btnSubmit.removeAttribute('disabled');
            } else {
                teksRingkasanOngkir.textContent = '-';
                teksRingkasanTotal.textContent = formatRupiah(subtotal);
                btnSubmit.setAttribute('disabled', 'disabled');
            }
        } else {
            // Ambil di tempat
            blokEkspedisi.style.display = 'none';
            blokAlamatPengiriman.style.display = 'none';
            inputAlamatPengiriman.removeAttribute('required');

            ongkirTerpilih = 0;
            teksRingkasanOngkir.textContent = 'Rp 0';
            teksRingkasanTotal.textContent = formatRupiah(subtotal);
            btnSubmit.removeAttribute('disabled');
        }

        // Perbarui opsi pembayaran yang diizinkan
        perbaruiMetodePembayaran(metode);
    }

    // 4. Kelola Opsi Pembayaran Sesuai Metode Pengiriman
    function perbaruiMetodePembayaran(metodePengiriman) {
        const diizinkan = pembayaranPerMetode[metodePengiriman] || [];
        let checkedSudahValid = false;
        let opsiPertamaYangDiizinkan = null;

        radioMetodePembayaran.forEach(function (radio) {
            const card = radio.closest('.ongkir-radio-card');
            const boleh = diizinkan.includes(radio.value);

            if (boleh) {
                radio.removeAttribute('disabled');
                if (card) card.classList.remove('disabled');

                if (!opsiPertamaYangDiizinkan) {
                    opsiPertamaYangDiizinkan = radio;
                }

                if (radio.checked) {
                    checkedSudahValid = true;
                    if (card) card.classList.add('active');
                } else {
                    if (card) card.classList.remove('active');
                }
            } else {
                radio.setAttribute('disabled', 'disabled');
                radio.checked = false;
                if (card) {
                    card.classList.add('disabled');
                    card.classList.remove('active');
                }
            }
        });

        // Jika opsi yang sebelumnya tercentang tidak diizinkan lagi, pilih yang pertama diizinkan
        if (!checkedSudahValid && opsiPertamaYangDiizinkan) {
            opsiPertamaYangDiizinkan.checked = true;
            const card = opsiPertamaYangDiizinkan.closest('.ongkir-radio-card');
            if (card) card.classList.add('active');
        }
    }

    // 5. Pencarian Tujuan dengan Debounce 400ms
    if (inputCariTujuan) {
        inputCariTujuan.addEventListener('input', function () {
            const kata = inputCariTujuan.value.trim();

            if (timeoutDebounce) clearTimeout(timeoutDebounce);
            if (controllerCariTujuan) controllerCariTujuan.abort();

            dropdownHasilTujuan.innerHTML = '';
            dropdownHasilTujuan.style.display = 'none';

            if (kata.length < 3) {
                loadingTujuan.style.display = 'none';
                return;
            }

            loadingTujuan.style.display = 'block';

            timeoutDebounce = setTimeout(function () {
                controllerCariTujuan = new AbortController();

                const url = new URL(urlTujuan, window.location.origin);
                url.searchParams.set('q', kata);

                fetch(url.toString(), {
                    signal: controllerCariTujuan.signal,
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (res) {
                        return res.json().then(function (body) {
                            return { status: res.status, body: body };
                        });
                    })
                    .then(function (result) {
                        loadingTujuan.style.display = 'none';
                        dropdownHasilTujuan.innerHTML = '';

                        if (result.status === 200 && Array.isArray(result.body.data)) {
                            const daftar = result.body.data;
                            if (daftar.length === 0) {
                                const emptyItem = document.createElement('div');
                                emptyItem.className = 'ongkir-item-tujuan';
                                emptyItem.style.color = '#888';
                                emptyItem.textContent = 'Wilayah tidak ditemukan. Coba kata kunci lain.';
                                dropdownHasilTujuan.appendChild(emptyItem);
                            } else {
                                daftar.forEach(function (item) {
                                    const row = document.createElement('div');
                                    row.className = 'ongkir-item-tujuan';
                                    row.textContent = item.label;
                                    row.addEventListener('click', function () {
                                        pilihTujuan(item.id, item.label);
                                    });
                                    dropdownHasilTujuan.appendChild(row);
                                });
                            }
                            dropdownHasilTujuan.style.display = 'block';
                        } else {
                            const errItem = document.createElement('div');
                            errItem.className = 'ongkir-item-tujuan';
                            errItem.style.color = '#a52222';
                            errItem.textContent = result.body.pesan || 'Gagal memuat tujuan.';
                            dropdownHasilTujuan.appendChild(errItem);
                            dropdownHasilTujuan.style.display = 'block';
                        }
                    })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') {
                            loadingTujuan.style.display = 'none';
                        }
                    });
            }, 400);
        });
    }

    // Pilih Wilayah Tujuan
    function pilihTujuan(id, label) {
        inputTujuanId.value = id;
        inputTujuanLabel.value = label;

        teksTujuanTerpilih.textContent = label;
        wadahTujuanTerpilih.style.display = 'flex';
        wadahCariTujuan.style.display = 'none';
        dropdownHasilTujuan.style.display = 'none';
        if (inputCariTujuan) inputCariTujuan.value = '';

        // Otomatis panggil opsi kurir
        muatOpsiKurir(id);
    }

    // Ganti Tujuan
    if (btnGantiTujuan) {
        btnGantiTujuan.addEventListener('click', function () {
            inputTujuanId.value = '';
            inputTujuanLabel.value = '';
            inputKurirKode.value = '';
            inputLayananKurir.value = '';

            wadahTujuanTerpilih.style.display = 'none';
            wadahCariTujuan.style.display = 'block';
            daftarOpsiKurir.innerHTML = '';
            pesanKosongKurir.style.display = 'none';
            pesanErrorKurir.style.display = 'none';

            ongkirTerpilih = 0;
            teksRingkasanOngkir.textContent = '-';
            teksRingkasanTotal.textContent = formatRupiah(subtotal);
            btnSubmit.setAttribute('disabled', 'disabled');

            if (inputCariTujuan) {
                inputCariTujuan.focus();
            }
        });
    }

    // 6. Muat Opsi Tarif Kurir
    function muatOpsiKurir(tujuanId, targetKurirKode, targetLayananKurir) {
        daftarOpsiKurir.innerHTML = '';
        pesanKosongKurir.style.display = 'none';
        pesanErrorKurir.style.display = 'none';
        loadingKurir.style.display = 'block';

        const url = new URL(urlOpsi, window.location.origin);
        url.searchParams.set('tujuan_id', tujuanId);

        fetch(url.toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { status: res.status, body: body };
                });
            })
            .then(function (result) {
                loadingKurir.style.display = 'none';

                if (result.status === 200 && result.body.data && Array.isArray(result.body.data.opsi)) {
                    opsiTersediaSaatIni = result.body.data.opsi;

                    if (opsiTersediaSaatIni.length === 0) {
                        pesanKosongKurir.textContent = result.body.pesan || 'Tidak ada layanan pengiriman untuk tujuan ini.';
                        pesanKosongKurir.style.display = 'block';

                        ongkirTerpilih = 0;
                        inputKurirKode.value = '';
                        inputLayananKurir.value = '';
                        teksRingkasanOngkir.textContent = '-';
                        teksRingkasanTotal.textContent = formatRupiah(subtotal);
                        btnSubmit.setAttribute('disabled', 'disabled');
                    } else {
                        renderDaftarKurir(opsiTersediaSaatIni, targetKurirKode, targetLayananKurir);
                    }
                } else {
                    pesanErrorKurir.textContent = result.body.pesan || 'Layanan ongkir sedang tidak tersedia. Silakan coba lagi.';
                    pesanErrorKurir.style.display = 'block';

                    ongkirTerpilih = 0;
                    inputKurirKode.value = '';
                    inputLayananKurir.value = '';
                    teksRingkasanOngkir.textContent = '-';
                    teksRingkasanTotal.textContent = formatRupiah(subtotal);
                    btnSubmit.setAttribute('disabled', 'disabled');
                }
            })
            .catch(function () {
                loadingKurir.style.display = 'none';
                pesanErrorKurir.textContent = 'Gagal menghubungi layanan ongkos kirim. Silakan periksa koneksi internet Anda.';
                pesanErrorKurir.style.display = 'block';

                ongkirTerpilih = 0;
                inputKurirKode.value = '';
                inputLayananKurir.value = '';
                teksRingkasanOngkir.textContent = '-';
                teksRingkasanTotal.textContent = formatRupiah(subtotal);
                btnSubmit.setAttribute('disabled', 'disabled');
            });
    }

    // Render Kartu Pilihan Kurir
    function renderDaftarKurir(daftar, targetKurirKode, targetLayananKurir) {
        daftarOpsiKurir.innerHTML = '';
        let terpilihOtomatis = null;

        daftar.forEach(function (opsi, index) {
            const card = document.createElement('label');
            card.className = 'ongkir-opsi-item';

            const kiri = document.createElement('div');
            kiri.className = 'ongkir-opsi-kiri';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = '_pilihan_kurir';
            radio.value = opsi.kurir_kode + ':' + opsi.layanan;

            const info = document.createElement('div');

            const kurirNama = document.createElement('div');
            kurirNama.className = 'ongkir-opsi-kurir-nama';
            kurirNama.textContent = opsi.kurir_nama + ' (' + opsi.kurir_kode.toUpperCase() + ')';

            const layanan = document.createElement('div');
            layanan.className = 'ongkir-opsi-layanan';
            layanan.textContent = opsi.layanan + (opsi.deskripsi ? ' - ' + opsi.deskripsi : '');

            info.appendChild(kurirNama);
            info.appendChild(layanan);
            kiri.appendChild(radio);
            kiri.appendChild(info);

            const kanan = document.createElement('div');
            kanan.className = 'ongkir-opsi-kanan';

            const biaya = document.createElement('div');
            biaya.className = 'ongkir-opsi-biaya';
            biaya.textContent = formatRupiah(opsi.biaya);

            const estimasi = document.createElement('div');
            estimasi.className = 'ongkir-opsi-estimasi';
            estimasi.textContent = 'Estimasi: ' + (opsi.estimasi || '-');

            kanan.appendChild(biaya);
            kanan.appendChild(estimasi);

            card.appendChild(kiri);
            card.appendChild(kanan);

            // Handler klik pemilihan kurir
            card.addEventListener('click', function () {
                pilihOpsiKurir(opsi, card);
            });

            daftarOpsiKurir.appendChild(card);

            // Cek apakah cocok dengan target kurir lama (dari old())
            if (targetKurirKode && targetLayananKurir) {
                if (opsi.kurir_kode.toLowerCase() === targetKurirKode.toLowerCase() &&
                    opsi.layanan.toLowerCase() === targetLayananKurir.toLowerCase()) {
                    terpilihOtomatis = { opsi: opsi, card: card };
                }
            }
        });

        if (terpilihOtomatis) {
            terpilihOtomatis.card.querySelector('input[type="radio"]').checked = true;
            pilihOpsiKurir(terpilihOtomatis.opsi, terpilihOtomatis.card);
        } else {
            // Belum ada yang dipilih
            ongkirTerpilih = 0;
            inputKurirKode.value = '';
            inputLayananKurir.value = '';
            teksRingkasanOngkir.textContent = '-';
            teksRingkasanTotal.textContent = formatRupiah(subtotal);
            btnSubmit.setAttribute('disabled', 'disabled');
        }
    }

    // Pilih Opsi Kurir
    function pilihOpsiKurir(opsi, cardElement) {
        inputKurirKode.value = opsi.kurir_kode;
        inputLayananKurir.value = opsi.layanan;
        ongkirTerpilih = parseInt(opsi.biaya, 10);

        // Highlight card aktif
        const semuaKartu = daftarOpsiKurir.querySelectorAll('.ongkir-opsi-item');
        semuaKartu.forEach(function (c) { c.classList.remove('selected'); });
        cardElement.classList.add('selected');
        cardElement.querySelector('input[type="radio"]').checked = true;

        // Perbarui ringkasan belanja
        teksRingkasanOngkir.textContent = formatRupiah(ongkirTerpilih);
        teksRingkasanTotal.textContent = formatRupiah(subtotal + ongkirTerpilih);

        // Aktifkan tombol submit
        btnSubmit.removeAttribute('disabled');
    }

    // 7. Event Listeners
    radioMetodePengiriman.forEach(function (radio) {
        radio.addEventListener('change', function () {
            perbaruiMetodePengiriman(radio.value);
        });
    });

    radioMetodePembayaran.forEach(function (radio) {
        radio.addEventListener('change', function () {
            radioMetodePembayaran.forEach(function (r) {
                const c = r.closest('.ongkir-radio-card');
                if (c) {
                    if (r.checked) c.classList.add('active');
                    else c.classList.remove('active');
                }
            });
        });
    });

    // 8. Inisialisasi awal saat halaman dimuat (memulihkan data old() jika ada)
    let metodeAwal = oldMetodePengiriman;
    const radioAwal = form.querySelector('input[name="metode_pengiriman"]:checked');
    if (radioAwal) {
        metodeAwal = radioAwal.value;
    }

    perbaruiMetodePengiriman(metodeAwal);

    if (metodeAwal === 'ekspedisi' && oldTujuanId) {
        pilihTujuan(oldTujuanId, oldTujuanLabel || ('Tujuan #' + oldTujuanId));
        muatOpsiKurir(oldTujuanId, oldKurirKode, oldLayananKurir);
    }
});

