(function () {
    const C = window.CUSTOMER;
    const PRODUCTS = C.products;
    const RATE = Number(C.service) || 0;
    const KEY = 'pos_orders_' + C.slug;
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    // Berapa menu yang tampil sekaligus, dan seberapa sering status pesanan diperbarui (milidetik).
    const PER = 15;
    const SEGARKAN_MS = 3000;

    const TAHAP = [
        ['antrian', 'Pesanan diterima'],
        ['diambil', 'Diambil dapur'],
        ['dimasak', 'Sedang dimasak'],
        ['serving', 'Siap diantar'],
        ['selesai', 'Selesai'],
    ];

    const URUTAN = [
        'Appetizer', 'Salad', 'Soup', 'Burger', 'Pizza', 'Pasta', 'Main Course', 'Nasi', 'Mie & Kwetiaw', 'Ikan', 'Tambahan',
        'Tea', 'Mojito', 'Mocktail', 'Kopi', 'Manual Brew', 'Milk Based', 'Juice', 'Non-Kopi', 'Pastry', 'Makanan', 'Minuman',
    ];
    const GRUP_MAKANAN = ['Appetizer', 'Salad', 'Soup', 'Burger', 'Pizza', 'Pasta', 'Main Course', 'Nasi', 'Mie & Kwetiaw', 'Ikan', 'Tambahan', 'Makanan', 'Pastry'];
    const GRUP_MINUMAN = ['Tea', 'Mojito', 'Mocktail', 'Kopi', 'Manual Brew', 'Milk Based', 'Juice', 'Non-Kopi', 'Minuman'];
    const grupDari = (k) => (GRUP_MAKANAN.includes(k) ? 'Makanan' : (GRUP_MINUMAN.includes(k) ? 'Minuman' : 'Lainnya'));
    const urutan = (k) => { const i = URUTAN.indexOf(k); return i === -1 ? 999 : i; };

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    let cart = {};            // id -> qty
    let kategori = 'Semua';
    let grup = 'Semua';
    let cari = '';
    let tampilN = PER;
    let sending = false;
    let current = null;       // kode pesanan yang sedang di layar QRIS
    const ORDERS = {};        // kode -> data pesanan terbaru dari server
    let codes = loadCodes();

    function loadCodes() {
        try {
            const v = JSON.parse(localStorage.getItem(KEY) || '[]');
            return Array.isArray(v) ? v.filter((x) => typeof x === 'string').slice(0, 5) : [];
        } catch (e) { return []; }
    }
    function saveCodes() {
        try { localStorage.setItem(KEY, JSON.stringify(codes)); } catch (e) { /* penyimpanan tidak tersedia */ }
    }

    const produk = (id) => PRODUCTS.find((p) => p.id === id);
    const jumlah = () => Object.values(cart).reduce((a, b) => a + b, 0);
    const maks = (p) => (p.track ? Math.min(p.stock, 99) : 99);

    // Aturan yang sama dengan server (Order::place): service charge hanya dari menu yang kena service.
    function hitung() {
        let sub = 0, elig = 0;
        Object.entries(cart).forEach(([id, q]) => {
            const p = produk(Number(id));
            const t = p.price * q;
            sub += t;
            if (p.disk) elig += t;
        });
        const service = RATE > 0 ? Math.round(elig * RATE / 100) : 0;
        return { sub: sub, service: service, grand: sub + service };
    }

    function show(nama) {
        ['menu', 'cart', 'qris', 'status'].forEach((v) => { $('v-' + v).hidden = v !== nama; });
        const h = $('h-' + nama);
        if (h) h.focus({ preventScroll: true });
        window.scrollTo(0, 0);
    }

    function pesan(teks, ok) { const el = $('pesan'); el.textContent = teks; el.className = ok ? 'ok' : 'err'; }

    /* ---------- menu: pencarian, grup, kategori ---------- */
    function terfilter() {
        const q = cari.trim().toLowerCase();
        return PRODUCTS.filter((p) => {
            if (q) return p.name.toLowerCase().includes(q);   // saat mencari, semua kategori ikut dicari
            if (kategori !== 'Semua') return p.category === kategori;
            return grup === 'Semua' || grupDari(p.category) === grup;
        }).sort((a, b) => urutan(a.category) - urutan(b.category) || a.name.localeCompare(b.name));
    }

    function renderKategori() {
        const hitungKat = {};
        PRODUCTS.forEach((p) => { if (p.category) hitungKat[p.category] = (hitungKat[p.category] || 0) + 1; });
        const semuaKat = Object.keys(hitungKat).sort((a, b) => urutan(a) - urutan(b) || a.localeCompare(b));

        // Pilihan Makanan / Minuman hanya untuk menu yang besar.
        const adaGrup = ['Makanan', 'Minuman', 'Lainnya'].filter((g) => semuaKat.some((k) => grupDari(k) === g));
        const pakaiGrup = semuaKat.length >= 7 && adaGrup.length > 1;
        if (!pakaiGrup) grup = 'Semua';

        $('grup').hidden = !pakaiGrup;
        if (pakaiGrup) {
            $('grup').innerHTML = ['Semua'].concat(adaGrup).map((g) =>
                '<button type="button" data-grup="' + esc(g) + '" aria-pressed="' + (g === grup) + '">' + esc(g) + '</button>'
            ).join('');
        }

        const tampil = semuaKat.filter((k) => grup === 'Semua' || grupDari(k) === grup);
        const semua = tampil.reduce((a, k) => a + hitungKat[k], 0);
        $('cats').hidden = semuaKat.length < 2;
        $('cats').innerHTML = [['Semua', semua]].concat(tampil.map((k) => [k, hitungKat[k]])).map(([k, n]) =>
            '<button type="button" data-kat="' + esc(k) + '" aria-pressed="' + (k === kategori) + '">' + esc(k) + '<span class="ct">' + n + '</span></button>'
        ).join('');
    }

    function aksi(p) {
        const q = cart[p.id] || 0;
        if (p.track && p.stock <= 0) return '<button class="addbtn" disabled>Habis</button>';
        if (q === 0) return '<button class="addbtn" data-tambah="' + p.id + '" aria-label="Tambah ' + esc(p.name) + '">Tambah</button>';
        return '<div class="stp"><button data-kurang="' + p.id + '" aria-label="Kurangi ' + esc(p.name) + '">−</button><b>' + q + '</b>'
            + '<button data-tambah="' + p.id + '" aria-label="Tambah ' + esc(p.name) + '"' + (q >= maks(p) ? ' disabled' : '') + '>+</button></div>';
    }

    function renderMenu() {
        const semua = terfilter();
        const list = semua.slice(0, tampilN);
        $('kosong').hidden = semua.length > 0;
        $('menu').innerHTML = list.map((p) => {
            const habis = p.track && p.stock <= 0;
            const info = !p.track ? '' : (habis ? 'Habis' : (p.stock <= 5 ? 'Sisa ' + p.stock : ''));
            return '<div class="item' + (habis ? ' off' : '') + '"><div class="tile"></div>'
                + '<div class="info"><div class="nm">' + esc(p.name) + '</div><div class="hg">' + rp(p.price) + '</div>'
                + (info ? '<div class="sk ' + (habis ? '' : 'low') + '">' + info + '</div>' : '') + '</div>' + aksi(p) + '</div>';
        }).join('');

        const sisa = semua.length - list.length;
        $('lebih-wrap').innerHTML = sisa > 0
            ? '<button type="button" class="lebih" id="lebih">Tampilkan lebih banyak (' + sisa + ' menu lagi)</button>'
            : '';
    }

    function ulang() { tampilN = PER; renderMenu(); }

    function renderBar() {
        const n = jumlah();
        $('bar').hidden = n === 0;
        $('bar-info').textContent = n + ' item di pesanan';
        $('bar-total').textContent = rp(hitung().grand);
    }

    function ubah(id, d) {
        const p = produk(id);
        if (!p) return;
        const n = (cart[id] || 0) + d;
        if (n <= 0) delete cart[id];
        else if (n <= maks(p)) cart[id] = n;
        renderMenu(); renderBar(); renderCart();
    }

    /* ---------- keranjang ---------- */
    function renderCart() {
        const ids = Object.keys(cart);
        $('cart-list').innerHTML = ids.length ? ids.map((id) => {
            const p = produk(Number(id)), q = cart[id];
            return '<div class="item"><div class="info"><div class="nm">' + esc(p.name) + '</div>'
                + '<div class="muted">' + rp(p.price) + ' × ' + q + ' = ' + rp(p.price * q) + '</div></div>'
                + '<div class="stp"><button data-kurang="' + id + '" aria-label="Kurangi ' + esc(p.name) + '">−</button><b>' + q + '</b>'
                + '<button data-tambah="' + id + '" aria-label="Tambah ' + esc(p.name) + '"' + (q >= maks(p) ? ' disabled' : '') + '>+</button></div></div>';
        }).join('') : '<p class="muted">Pesanan masih kosong.</p>';

        const h = hitung();
        $('c-sub').textContent = rp(h.sub);
        $('c-service-row').hidden = h.service <= 0;
        $('c-service-label').textContent = 'Service charge ' + RATE + '%';
        $('c-service').textContent = rp(h.service);
        $('cart-total').textContent = rp(h.grand);
        $('kirim').disabled = sending || ids.length === 0;
    }

    function metode() {
        const r = document.querySelector('input[name=pay]:checked');
        return r ? r.value : 'qris';
    }

    async function api(url, opsi) {
        const r = await fetch(url, opsi);
        const d = await r.json().catch(() => ({}));
        if (!r.ok) {
            const detail = d.errors ? Object.values(d.errors).flat().join(' ') : '';
            const err = new Error(detail || d.message || ('Gagal (' + r.status + ')'));
            err.status = r.status;
            throw err;
        }
        return d;
    }

    const post = (url, body) => api(url, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body || {}),
    });

    async function kirim() {
        if (sending || !jumlah()) return;
        sending = true; $('kirim').disabled = true; pesan('Mengirim pesanan…', true);
        const pakaiQris = metode() === 'qris';
        try {
            const d = await post(C.urlStore, {
                items: Object.entries(cart).map(([id, qty]) => ({ id: Number(id), qty: qty })),
                pay_preference: pakaiQris ? 'meja' : 'kasir',
            });
            const o = d.order;
            ORDERS[o.code] = o;
            codes = [o.code].concat(codes.filter((c) => c !== o.code)).slice(0, 5);
            saveCodes();

            Object.entries(cart).forEach(([id, qty]) => { const p = produk(Number(id)); if (p && p.track) p.stock = Math.max(0, p.stock - qty); });
            cart = {};
            pesan('', true);
            renderMenu(); renderBar(); renderCart();

            if (pakaiQris) { current = o.code; renderQris(); show('qris'); }
            else { renderStatus(); show('status'); }
        } catch (e) {
            pesan(e.message, false);
        }
        sending = false; renderCart();
    }

    /* ---------- QRIS ---------- */
    function renderQris() {
        const o = ORDERS[current];
        if (!o) return;
        $('qris-total').textContent = rp(o.grand_total);
        $('qris-service').textContent = o.service > 0 ? 'Termasuk service charge ' + rp(o.service) : '';
        $('qris-kode').textContent = o.code;
        const box = $('qr');
        box.innerHTML = '';
        if (window.QRCode) {
            // Isi QR di bawah hanya contoh. Ganti dengan string QRIS dari payment gateway.
            new QRCode(box, { text: 'DEMO-QRIS|' + o.code + '|' + o.grand_total, width: 220, height: 220 });
        } else {
            box.textContent = 'Kode QR tidak dapat dimuat. Periksa koneksi internet, lalu muat ulang halaman.';
        }
    }

    async function sudahBayar() {
        const tombol = $('sudah-bayar');
        if (!current || !tombol) return;
        tombol.disabled = true;
        try {
            const d = await post(C.urlOrder + '/' + encodeURIComponent(current) + '/bayar');
            ORDERS[current] = d.order;
            renderStatus(); show('status');
        } catch (e) {
            alert(e.message);
        }
        tombol.disabled = false;
    }

    /* ---------- status ---------- */
    function labelBayar(o) {
        if (o.status === 'batal') return '<span class="badge bad">Dibatalkan</span>';
        if (o.status === 'lunas') return '<span class="badge ok">Lunas' + (o.payment_method ? ' · ' + esc(String(o.payment_method).toUpperCase()) : '') + '</span>';
        return o.pay_preference === 'meja'
            ? '<span class="badge wait">Menunggu pembayaran QRIS</span>'
            : '<span class="badge wait">Bayar di kasir</span>';
    }

    function kartu(o) {
        const idx = TAHAP.findIndex((t) => t[0] === o.kitchen_status);
        let isi = '<div class="card"><div class="row"><b>' + esc(o.code) + '</b>' + labelBayar(o) + '</div>';
        if (o.status === 'batal') {
            isi += '<p class="muted" style="margin-top:8px">Pesanan ini dibatalkan. Silakan hubungi kasir.</p>';
        } else {
            isi += '<ol class="steps">' + TAHAP.map((t, i) =>
                '<li class="' + (i < idx ? 'done' : (i === idx ? 'now' : '')) + '"' + (i === idx ? ' aria-current="step"' : '') + '>' + t[1] + '</li>'
            ).join('') + '</ol>';
        }
        if (o.status === 'menunggu_bayar' && o.pay_preference === 'meja') {
            isi += '<button class="btn sec" style="margin-bottom:10px" data-qris="' + esc(o.code) + '">Bayar dengan QRIS</button>';
        }
        isi += '<div class="isi">' + esc(o.items.map((i) => i.qty + 'x ' + i.name).join(', '))
            + (o.service > 0 ? '<br>Service charge ' + rp(o.service) : '')
            + '<br><b>Total ' + rp(o.grand_total) + '</b></div></div>';
        return isi;
    }

    function renderStatus() {
        const list = codes.map((c) => ORDERS[c]).filter(Boolean);
        $('orders').innerHTML = list.length ? list.map(kartu).join('') : '<p class="muted">Belum ada pesanan.</p>';
    }

    const selesaiTotal = (o) => o.status === 'batal' || (o.kitchen_status === 'selesai' && o.status === 'lunas');

    function renderAktifInfo() {
        const n = codes.map((c) => ORDERS[c]).filter((o) => o && !selesaiTotal(o)).length;
        $('aktif-info').hidden = n === 0;
        $('aktif-teks').textContent = n + ' pesanan sedang diproses.';
    }

    function setLive(teks, ok) {
        const el = $('live');
        el.textContent = teks;
        el.className = 'live' + (ok ? '' : ' err');
    }

    async function segarkan() {
        if (document.hidden) return;
        const aktifKode = codes.filter((c) => !ORDERS[c] || !selesaiTotal(ORDERS[c]));
        if (!aktifKode.length) { setLive('Semua pesanan sudah selesai.', true); renderAktifInfo(); return; }

        let gagal = false;
        await Promise.all(aktifKode.map(async (c) => {
            try {
                const d = await api(C.urlStatus + '/' + encodeURIComponent(c), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                ORDERS[c] = d.order;
            } catch (e) {
                if (e.status === 404) { delete ORDERS[c]; codes = codes.filter((x) => x !== c); saveCodes(); }
                else gagal = true;
            }
        }));

        // Pindah dari layar QRIS ke status begitu pesanan lunas.
        if (!$('v-qris').hidden && current && ORDERS[current] && ORDERS[current].status === 'lunas') { renderStatus(); show('status'); }
        if (!$('v-status').hidden) renderStatus();
        renderAktifInfo();

        if (gagal) setLive('Koneksi terputus, mencoba lagi…', false);
        else setLive('● Terhubung · diperbarui ' + new Date().toLocaleTimeString('id-ID'), true);
    }

    /* ---------- event ---------- */
    function klikItem(e) {
        const t = e.target.closest('[data-tambah]'), k = e.target.closest('[data-kurang]');
        if (t) ubah(Number(t.dataset.tambah), 1);
        if (k) ubah(Number(k.dataset.kurang), -1);
    }
    $('menu').addEventListener('click', klikItem);
    $('cart-list').addEventListener('click', klikItem);
    $('lebih-wrap').addEventListener('click', (e) => { if (e.target.closest('#lebih')) { tampilN += PER; renderMenu(); } });
    $('grup').addEventListener('click', (e) => {
        const b = e.target.closest('[data-grup]'); if (!b) return;
        grup = b.dataset.grup; kategori = 'Semua'; renderKategori(); ulang();
    });
    $('cats').addEventListener('click', (e) => {
        const b = e.target.closest('[data-kat]'); if (!b) return;
        kategori = b.dataset.kat; renderKategori(); ulang();
    });
    $('cari').addEventListener('input', () => { cari = $('cari').value; ulang(); });
    $('ke-cart').addEventListener('click', () => { pesan('', true); renderCart(); show('cart'); });
    $('kembali-menu').addEventListener('click', () => show('menu'));
    $('kirim').addEventListener('click', kirim);
    $('qris-status').addEventListener('click', () => { renderStatus(); show('status'); });
    if ($('sudah-bayar')) $('sudah-bayar').addEventListener('click', sudahBayar);
    $('lihat-status').addEventListener('click', () => { renderStatus(); show('status'); });
    $('pesan-lagi').addEventListener('click', () => show('menu'));
    $('orders').addEventListener('click', (e) => {
        const b = e.target.closest('[data-qris]'); if (!b) return;
        current = b.dataset.qris; renderQris(); show('qris');
    });
    // Begitu layar HP dibuka lagi, langsung perbarui status tanpa menunggu putaran berikutnya.
    document.addEventListener('visibilitychange', () => { if (!document.hidden) segarkan(); });

    renderKategori(); renderMenu(); renderBar(); renderCart();
    segarkan();
    setInterval(segarkan, SEGARKAN_MS);
})();
