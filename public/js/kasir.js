(function () {
    const CFG = window.KASIR;
    const URL_DATA = CFG.urlData;
    const URL_STORE = CFG.urlStore;
    const URL_ORDER = CFG.urlOrder;
    const URL_MENU = CFG.urlMenu;
    const URL_MENU_BASE = CFG.urlMenuBase;
    const OUTLET = CFG.outlet;
    const TABLES = CFG.tables;
    const KITCHEN = CFG.kitchen;
    const BARCODE = CFG.barcode;
    const DISKON_MAKS = Number(CFG.diskonMaks) || 50;
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    // Seberapa sering data kasir diperbarui (milidetik). 3000 = tiap 3 detik.
    const SEGARKAN_MS = 3000;

    // Kalau jumlah kategori yang tampil lebih dari angka ini, di HP dipakai dropdown (bukan deretan tombol).
    const BATAS_TOMBOL = 6;

    // Katalog menu: outlet ini lebih dulu, lalu outlet yang terhubung (kalau ada).
    const OWN = { outlet: CFG.outlet, service: Number(CFG.service) || 0, products: CFG.products };
    const KATALOG = [OWN].concat((CFG.linked || []).map((k) => ({ outlet: k.outlet, service: Number(k.service) || 0, products: k.products })));
    KATALOG.forEach((k) => k.products.forEach((p) => { p.outlet = k.outlet; }));
    const SEMUA = KATALOG.reduce((a, k) => a.concat(k.products), []);
    let aktif = OWN.outlet;   // katalog yang sedang ditampilkan

    // Jumlah menu per halaman. Ubah angka ini kalau mau lebih sedikit atau lebih banyak.
    const PER_HALAMAN = 12;

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // Urutan tampil kategori. Kategori yang tidak ada di daftar ini ditaruh di belakang, urut abjad.
    const URUTAN = [
        'Appetizer', 'Salad', 'Soup', 'Burger', 'Pizza', 'Pasta', 'Main Course', 'Nasi', 'Mie & Kwetiaw', 'Ikan', 'Tambahan',
        'Tea', 'Mojito', 'Mocktail', 'Kopi', 'Manual Brew', 'Milk Based', 'Juice', 'Non-Kopi', 'Pastry',
        'Paket Jam', 'Aksesoris', 'Makanan', 'Sembako', 'Snack', 'Kebutuhan Rumah', 'Minuman', 'Parkir',
    ];

    // Pengelompokan untuk menu yang besar. Kategori di luar dua daftar ini masuk "Lainnya".
    const GRUP_MAKANAN = ['Appetizer', 'Salad', 'Soup', 'Burger', 'Pizza', 'Pasta', 'Main Course', 'Nasi', 'Mie & Kwetiaw', 'Ikan', 'Tambahan', 'Makanan', 'Pastry'];
    const GRUP_MINUMAN = ['Tea', 'Mojito', 'Mocktail', 'Kopi', 'Manual Brew', 'Milk Based', 'Juice', 'Non-Kopi', 'Minuman'];
    const grupDari = (k) => (GRUP_MAKANAN.includes(k) ? 'Makanan' : (GRUP_MINUMAN.includes(k) ? 'Minuman' : 'Lainnya'));
    const urutan = (k) => { const i = URUTAN.indexOf(k); return i === -1 ? 999 : i; };

    let cart = {};          // id -> qty (boleh campuran antar outlet)
    let metode = 'tunai';
    let sending = false;
    let grup = 'Semua';
    let kategori = 'Semua';
    let cari = '';
    let halaman = 1;
    let infoTimer;
    let toastTimer;
    let dikenal = null;     // id pesanan customer yang sudah pernah terlihat (null = belum ada data pertama)
    let kelola = false;     // true = mode kelola menu (klik menu untuk ubah / hapus)
    let editId = null;      // id menu yang sedang diubah di jendela (null = menambah menu baru)

    // Daftar menu yang ditampilkan di kasir. Parkir tidak ikut (sudah ada tombol cepat), kecuali di mode kelola.
    const daftar = () => (KATALOG.find((k) => k.outlet === aktif) || OWN).products.filter((p) => kelola || p.category !== 'Parkir');
    const produk = (id) => SEMUA.find((p) => p.id === id);
    const rateOutlet = (o) => (KATALOG.find((k) => k.outlet === o) || OWN).service;
    const bisaTambah = (p) => !p.track || (cart[p.id] || 0) < p.stock;

    /* ---------- notifikasi dan bunyi ---------- */
    function toast(teks) {
        const el = $('toast');
        el.textContent = teks;
        el.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { el.hidden = true; }, 8000);
    }

    function bip() {
        try {
            const c = new (window.AudioContext || window.webkitAudioContext)();
            const o = c.createOscillator();
            const g = c.createGain();
            o.connect(g);
            g.connect(c.destination);
            o.frequency.value = 880;
            g.gain.value = 0.1;
            o.onended = () => c.close();
            o.start();
            o.stop(c.currentTime + 0.3);
        } catch (e) { /* suara tidak tersedia, abaikan */ }
    }

    // Beri tahu kasir kalau ada pesanan customer yang baru masuk.
    function cekPesananBaru(orders) {
        const cust = orders.filter((o) => o.source === 'customer');
        const ids = new Set(cust.map((o) => o.id));

        if (dikenal === null) { dikenal = ids; return; }   // data pertama: jadikan patokan, tanpa bunyi

        const baru = cust.filter((o) => !dikenal.has(o.id));
        ids.forEach((id) => dikenal.add(id));

        if (baru.length) {
            bip();
            toast('Pesanan baru: ' + baru.map((o) =>
                o.table + ' (' + rp(o.grand_total) + ', ' + (o.pay_preference === 'meja' ? 'QRIS' : 'bayar di kasir') + ')'
            ).join(', '));
        }
    }

    /* ---------- hitung total: subtotal, diskon, service charge ---------- */
    // Aturan ini sama dengan yang dihitung server (KasirOrderController::store dan Order::place).
    function hitung() {
        const per = {};   // outlet -> {sub, elig}
        Object.entries(cart).forEach(([id, q]) => {
            const p = produk(Number(id));
            const t = p.price * q;
            const x = per[p.outlet] || (per[p.outlet] = { sub: 0, elig: 0 });
            x.sub += t;
            if (p.disk) x.elig += t;
        });

        const urutOutlet = KATALOG.map((k) => k.outlet).filter((o) => per[o]);
        const subtotal = urutOutlet.reduce((a, o) => a + per[o].sub, 0);
        const totalElig = urutOutlet.reduce((a, o) => a + per[o].elig, 0);

        const tipe = $('d-tipe').value;
        const nilai = Number($('d-nilai').value) || 0;
        let diskon = 0;
        let salah = '';

        if (!Number.isInteger(nilai) || nilai < 0) {
            salah = 'Isi diskon dengan angka bulat 0 atau lebih.';
        } else if (nilai > 0) {
            if (totalElig <= 0) salah = 'Tidak ada menu yang bisa didiskon (parkir, paket jam, dan aksesoris tidak kena diskon).';
            else if (tipe === 'persen') {
                if (nilai > DISKON_MAKS) salah = 'Diskon maksimal ' + DISKON_MAKS + '%.';
                else diskon = Math.round(totalElig * nilai / 100);
            } else if (nilai > totalElig) salah = 'Diskon melebihi total menu yang bisa didiskon (' + rp(totalElig) + ').';
            else diskon = nilai;
        }

        // Bagi diskon per outlet, outlet terakhir mendapat sisanya.
        const share = {};
        let sisa = diskon;
        urutOutlet.forEach((o, i) => {
            if (i === urutOutlet.length - 1) share[o] = Math.max(0, Math.min(sisa, per[o].elig));
            else {
                const b = totalElig > 0 ? Math.min(Math.round(diskon * per[o].elig / totalElig), per[o].elig) : 0;
                share[o] = b;
                sisa -= b;
            }
        });

        let service = 0;
        const rates = new Set();
        urutOutlet.forEach((o) => {
            const r = rateOutlet(o);
            if (r > 0) {
                const dasar = per[o].elig - share[o];
                service += Math.round(dasar * r / 100);
                if (per[o].elig > 0) rates.add(r);
            }
        });

        const diskonPakai = urutOutlet.reduce((a, o) => a + share[o], 0);
        const labelService = rates.size === 1 ? 'Service charge ' + Array.from(rates)[0] + '%' : 'Service charge';

        return {
            subtotal: subtotal,
            diskon: diskonPakai,
            service: service,
            adaService: rates.size > 0,
            labelService: labelService,
            grand: subtotal - diskonPakai + service,
            salah: salah,
        };
    }

    /* ---------- produk terlaris ---------- */
    // 5 produk dengan jumlah terjual terbanyak (30 hari terakhir). Produk yang belum pernah terjual tidak ikut.
    function laris() {
        return daftar().filter((p) => (p.sold || 0) > 0)
            .sort((a, b) => b.sold - a.sold || a.name.localeCompare(b.name))
            .slice(0, 5);
    }

    function peringkat() {
        const m = {};
        laris().slice(0, 3).forEach((p, i) => { m[p.id] = i + 1; });
        return m;
    }

    function renderLaris() {
        const list = laris();
        $('box-laris').hidden = kelola || list.length === 0;
        $('laris').innerHTML = list.map((p, i) => {
            const nonaktif = p.track && (p.stock <= 0 || !bisaTambah(p));
            return '<button type="button" class="lr" data-laris="' + p.id + '"' + (nonaktif ? ' disabled' : '') + '>'
                + '<span class="lbadge">#' + (i + 1) + '</span><b>' + esc(p.name) + '</b>'
                + '<span class="hrg2">' + rp(p.price) + '</span>'
                + '<span class="stok">Terjual ' + p.sold + '</span></button>';
        }).join('');
    }

    function pesan(teks, ok) { const el = $('pesan'); el.textContent = teks; el.className = ok ? 'ok' : 'err'; }

    /* ---------- pilihan katalog (outlet ini / outlet terhubung) ---------- */
    function renderKatalog() {
        const lain = aktif !== OWN.outlet;
        $('katalog').hidden = KATALOG.length < 2;
        $('katalog').innerHTML = KATALOG.map((k) =>
            '<button type="button" data-katalog="' + esc(k.outlet) + '" aria-pressed="' + (k.outlet === aktif) + '">'
            + '<span class="lg">Menu </span>' + esc(k.outlet)
            + (k.outlet === OWN.outlet ? ' <span class="ct hp-sembunyi">outlet ini</span>' : '') + '</button>'
        ).join('');

        $('info-katalog').hidden = !lain;
        $('info-katalog').textContent = lain
            ? 'Menu ' + aktif + ': penjualan dan stok dicatat di ' + aktif + ', dan kalau perlu dimasak pesanannya masuk ke dapur ' + aktif + '. Pembayarannya tetap satu kali di sini.'
            : '';
        $('buka-menu').hidden = lain;      // menu outlet lain tidak bisa ditambah atau diubah dari sini
        $('kelola').hidden = lain;
    }

    function gantiKatalog(outlet) {
        if (kelola) aturKelola(false);
        aktif = outlet;
        grup = 'Semua'; kategori = 'Semua'; cari = ''; $('cari').value = ''; halaman = 1;
        renderKatalog(); renderKategori(); renderMenu();
    }

    /* ---------- pencarian, grup, dan kategori ---------- */
    // Daftar lengkap menu yang cocok (belum dipotong per halaman).
    function terfilter() {
        const q = cari.trim().toLowerCase();
        return daftar().filter((p) => {
            // Saat mencari, semua kategori ikut dicari (termasuk saat scan barcode).
            if (q) return p.name.toLowerCase().includes(q) || (p.barcode || '').includes(q);
            if (kategori !== 'Semua') return p.category === kategori;
            return grup === 'Semua' || grupDari(p.category) === grup;
        }).sort((a, b) => (b.sold || 0) - (a.sold || 0) || a.name.localeCompare(b.name));   // yang terlaris tampil paling atas
    }

    function renderKategori() {
        const semuaMenu = daftar();
        const hitungKat = {};
        semuaMenu.forEach((p) => { if (p.category) hitungKat[p.category] = (hitungKat[p.category] || 0) + 1; });
        const semuaKat = Object.keys(hitungKat).sort((a, b) => urutan(a) - urutan(b) || a.localeCompare(b));

        // Kalau kategori yang sedang dibuka sudah tidak ada (misalnya menunya dihapus atau dipindah), kembali ke "Semua".
        if (kategori !== 'Semua' && !hitungKat[kategori]) kategori = 'Semua';

        // Pilihan Makanan / Minuman hanya untuk menu yang besar (7 kategori atau lebih).
        const adaGrup = ['Makanan', 'Minuman', 'Lainnya'].filter((g) => semuaKat.some((k) => grupDari(k) === g));
        const pakaiGrup = semuaKat.length >= 7 && adaGrup.length > 1;
        if (!pakaiGrup || (grup !== 'Semua' && !adaGrup.includes(grup))) grup = 'Semua';

        $('grup').hidden = !pakaiGrup;
        if (pakaiGrup) {
            $('grup').innerHTML = ['Semua'].concat(adaGrup).map((g) => {
                const n = g === 'Semua' ? semuaMenu.length : semuaMenu.filter((p) => grupDari(p.category) === g).length;
                return '<button type="button" data-grup="' + esc(g) + '" aria-pressed="' + (g === grup) + '">' + esc(g) + '<span class="ct">' + n + '</span></button>';
            }).join('');
        }

        const tampilKat = semuaKat.filter((k) => grup === 'Semua' || grupDari(k) === grup);
        const jumlahSemua = tampilKat.reduce((a, k) => a + hitungKat[k], 0);
        const banyak = tampilKat.length > BATAS_TOMBOL;

        // Deretan tombol kategori. Kalau menunya besar dan belum memilih Makanan / Minuman, tidak ditampilkan
        // supaya tidak menjabarkan semua kategori sekaligus.
        const adaTombol = semuaKat.length >= 2 && !(pakaiGrup && grup === 'Semua');
        $('cats').hidden = !adaTombol;
        $('cats').classList.toggle('banyak', banyak);
        $('cats').innerHTML = [['Semua', jumlahSemua]].concat(tampilKat.map((k) => [k, hitungKat[k]])).map(([k, n]) =>
            '<button type="button" data-kat="' + esc(k) + '" aria-pressed="' + (k === kategori) + '">' + esc(k) + '<span class="ct">' + n + '</span></button>'
        ).join('');

        // Dropdown kategori (dipakai di HP kalau kategorinya banyak).
        const opsi = (k) => '<option value="' + esc(k) + '"' + (k === kategori ? ' selected' : '') + '>' + esc(k) + ' (' + hitungKat[k] + ')</option>';
        let html;
        if (pakaiGrup && grup === 'Semua') {
            html = '<option value="Semua"' + (kategori === 'Semua' ? ' selected' : '') + '>Semua kategori (' + semuaMenu.length + ')</option>'
                + adaGrup.map((g) => '<optgroup label="' + esc(g) + '">' + semuaKat.filter((k) => grupDari(k) === g).map(opsi).join('') + '</optgroup>').join('');
        } else {
            html = '<option value="Semua"' + (kategori === 'Semua' ? ' selected' : '') + '>Semua' + (grup !== 'Semua' ? ' ' + esc(grup) : ' kategori') + ' (' + jumlahSemua + ')</option>'
                + tampilKat.map(opsi).join('');
        }
        $('kat-select').innerHTML = html;
        $('kat-wrap').hidden = semuaKat.length < 2;
        $('kat-wrap').classList.toggle('banyak', banyak);
    }

    /* ---------- paginasi ---------- */
    // Nomor halaman yang ditampilkan: pertama, terakhir, dan sekitar halaman aktif. Sisanya diringkas "…".
    function nomorHalaman(sekarang, maks) {
        const nomor = new Set([1, maks, sekarang - 1, sekarang, sekarang + 1]);
        if (maks <= 7) for (let i = 1; i <= maks; i++) nomor.add(i);
        const urut = Array.from(nomor).filter((n) => n >= 1 && n <= maks).sort((a, b) => a - b);
        const hasil = [];
        let sebelum = 0;
        urut.forEach((n) => {
            if (n - sebelum > 1) hasil.push('…');
            hasil.push(n);
            sebelum = n;
        });
        return hasil;
    }

    function renderHalaman(jumlah, maks, mulai, tampil) {
        const el = $('halaman');
        el.hidden = maks <= 1;
        if (maks <= 1) { el.innerHTML = ''; return; }

        const tombol = nomorHalaman(halaman, maks).map((n) => (n === '…'
            ? '<span class="titik" aria-hidden="true">…</span>'
            : '<button type="button" data-hal="' + n + '"' + (n === halaman ? ' aria-current="page"' : '') + ' aria-label="Halaman ' + n + '">' + n + '</button>'
        )).join('');

        el.innerHTML = '<div class="hal-info">Menampilkan ' + (mulai + 1) + '–' + (mulai + tampil) + ' dari ' + jumlah + ' menu</div>'
            + '<nav class="hal-nav" aria-label="Halaman menu">'
            + '<button type="button" data-hal="' + (halaman - 1) + '"' + (halaman <= 1 ? ' disabled' : '') + ' aria-label="Halaman sebelumnya"><span aria-hidden="true">‹</span><span class="tx"> Sebelumnya</span></button>'
            + tombol
            + '<button type="button" data-hal="' + (halaman + 1) + '"' + (halaman >= maks ? ' disabled' : '') + ' aria-label="Halaman berikutnya"><span class="tx">Berikutnya </span><span aria-hidden="true">›</span></button>'
            + '</nav>';
    }

    function renderMenu() {
        const semua = terfilter();
        const maks = Math.max(1, Math.ceil(semua.length / PER_HALAMAN));
        if (halaman > maks) halaman = maks;
        if (halaman < 1) halaman = 1;

        const mulai = (halaman - 1) * PER_HALAMAN;
        const list = semua.slice(mulai, mulai + PER_HALAMAN);
        const rank = peringkat();

        $('kosong').hidden = semua.length > 0;
        $('menu').classList.toggle('mode-kelola', kelola);
        $('menu').innerHTML = list.map((p) => {
            // Di mode kelola semua menu bisa diklik (termasuk yang stoknya habis), karena tujuannya mengubah menu.
            const nonaktif = !kelola && p.track && (p.stock <= 0 || !bisaTambah(p));
            const info = !p.track ? 'Tanpa stok' : (p.stock <= 0 ? 'Habis' : 'Stok ' + p.stock);
            const lencana = kelola
                ? '<span class="lbadge ubah">Ubah</span>'
                : (rank[p.id] ? '<span class="lbadge">Terlaris #' + rank[p.id] + '</span>' : '');
            return `<button class="prod" data-id="${p.id}" ${nonaktif ? 'disabled' : ''}>
                <div class="tile"></div>${lencana}<b>${esc(p.name)}</b><div class="hrg">${rp(p.price)}</div>
                <div class="stok ${p.track && p.stock <= 5 ? 'low' : ''}">${info}</div></button>`;
        }).join('');

        renderHalaman(semua.length, maks, mulai, list.length);
        renderLaris();
    }

    // Ganti kategori, grup, atau kata pencarian: kembali ke halaman 1.
    function ulangDariAwal() { halaman = 1; renderMenu(); }

    /* ---------- parkir (tombol cepat) ---------- */
    function renderParkir() {
        const list = OWN.products.filter((p) => p.category === 'Parkir').sort((a, b) => b.price - a.price);
        $('parkir').hidden = list.length === 0;
        $('parkir-btn').innerHTML = list.map((p) =>
            '<button type="button" class="pk" data-parkir="' + p.id + '">+ ' + esc(p.name.replace(/^Parkir\s*/i, '')) + '<span>' + rp(p.price) + '</span></button>'
        ).join('');
    }

    /* ---------- keranjang ---------- */
    function renderCart() {
        const ids = Object.keys(cart);
        $('cart').innerHTML = ids.length ? ids.map((id) => {
            const p = produk(Number(id)), q = cart[id];
            const asal = p.outlet !== OWN.outlet ? '<span class="tag">' + esc(p.outlet) + '</span>' : '';
            return `<div class="line row"><div><b>${esc(p.name)}</b>${asal}<div class="muted">${rp(p.price)} × ${q} = ${rp(p.price * q)}</div></div>
                <div class="qty"><button data-kurang="${id}" aria-label="Kurangi ${esc(p.name)}">−</button><b>${q}</b><button data-tambah="${id}" aria-label="Tambah ${esc(p.name)}">+</button></div></div>`;
        }).join('') : '<p class="muted">Belum ada item. Pilih menu di sebelah kiri.</p>';
        hitungKembali(); renderMenu();
    }

    // Perbarui ringkasan (subtotal, diskon, service, total), tombol bayar, dan bar bawah di HP.
    function hitungKembali() {
        const h = hitung();
        $('r-sub').textContent = rp(h.subtotal);
        $('baris-diskon').hidden = h.diskon <= 0;
        $('r-diskon').textContent = '− ' + rp(h.diskon);
        $('baris-service').hidden = !h.adaService;
        $('r-service-label').textContent = h.labelService;
        $('r-service').textContent = rp(h.service);
        $('total').textContent = rp(h.grand);
        $('d-pesan').textContent = h.salah;

        const paid = Number($('paid').value) || 0;
        $('kembali').textContent = rp(Math.max(0, paid - h.grand));
        $('bayar').disabled = sending || h.grand <= 0 || !!h.salah || (metode === 'tunai' && paid < h.grand);

        const n = Object.values(cart).reduce((a, b) => a + b, 0);
        $('mbar').hidden = n === 0;
        $('mbar-info').textContent = n + ' item';
        $('mbar-total').textContent = rp(h.grand);
    }

    function tambah(id) {
        const p = produk(id);
        if (p && bisaTambah(p)) { cart[id] = (cart[id] || 0) + 1; renderCart(); }
    }

    // Klik menu: di mode kelola membuka jendela ubah, selain itu menambah ke pesanan.
    $('menu').addEventListener('click', (e) => {
        const b = e.target.closest('[data-id]'); if (!b) return;
        const id = Number(b.dataset.id);
        if (kelola) { const p = produk(id); if (p) bukaMenu(p); } else tambah(id);
    });
    $('laris').addEventListener('click', (e) => { const b = e.target.closest('[data-laris]'); if (b) tambah(Number(b.dataset.laris)); });
    $('parkir-btn').addEventListener('click', (e) => { const b = e.target.closest('[data-parkir]'); if (b) tambah(Number(b.dataset.parkir)); });
    $('katalog').addEventListener('click', (e) => { const b = e.target.closest('[data-katalog]'); if (b) gantiKatalog(b.dataset.katalog); });
    $('mbar-btn').addEventListener('click', () => { $('keranjang').scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    $('halaman').addEventListener('click', (e) => {
        const b = e.target.closest('[data-hal]');
        if (!b || b.disabled) return;
        halaman = Number(b.dataset.hal);
        renderMenu();
        $('tools').scrollIntoView({ behavior: 'smooth', block: 'start' });   // kembali ke atas daftar menu
    });
    $('grup').addEventListener('click', (e) => {
        const b = e.target.closest('[data-grup]'); if (!b) return;
        grup = b.dataset.grup; kategori = 'Semua';
        renderKategori(); ulangDariAwal();
    });
    $('cats').addEventListener('click', (e) => {
        const b = e.target.closest('[data-kat]'); if (!b) return;
        kategori = b.dataset.kat; renderKategori(); ulangDariAwal();
    });
    $('kat-select').addEventListener('change', () => {
        kategori = $('kat-select').value; renderKategori(); ulangDariAwal();
    });
    $('cari').addEventListener('input', () => { cari = $('cari').value; ulangDariAwal(); });
    $('cari').addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || kelola) return;
        e.preventDefault();
        const v = $('cari').value.trim();
        const hasil = terfilter();   // daftar lengkap, bukan hanya halaman yang tampil
        const p = daftar().find((x) => x.barcode && x.barcode === v) || (hasil.length === 1 ? hasil[0] : null);
        if (p) { tambah(p.id); $('cari').value = ''; cari = ''; ulangDariAwal(); pesan('', true); }
        else pesan('Produk tidak ditemukan, atau hasilnya lebih dari satu.', false);
    });
    $('cart').addEventListener('click', (e) => {
        const t = e.target.closest('[data-tambah]'), k = e.target.closest('[data-kurang]');
        if (t) { const id = Number(t.dataset.tambah); if (bisaTambah(produk(id))) cart[id]++; }
        if (k) { const id = Number(k.dataset.kurang); cart[id]--; if (cart[id] <= 0) delete cart[id]; }
        renderCart();
    });
    function kosongkanDiskon() { $('d-nilai').value = ''; $('d-note').value = ''; $('d-tipe').value = 'rupiah'; }
    $('kosongkan').addEventListener('click', () => { cart = {}; kosongkanDiskon(); renderCart(); pesan('', true); });
    $('paid').addEventListener('input', hitungKembali);
    $('d-tipe').addEventListener('change', hitungKembali);
    $('d-nilai').addEventListener('input', hitungKembali);
    $('metode').addEventListener('click', (e) => {
        const b = e.target.closest('[data-m]'); if (!b) return;
        metode = b.dataset.m;
        document.querySelectorAll('#metode button').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        $('box-tunai').hidden = metode !== 'tunai';
        hitungKembali();
    });

    async function kirim(url, body, method) {
        const m = method || 'POST';
        const r = await fetch(url, {
            method: m, credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: m === 'DELETE' ? undefined : JSON.stringify(body || {}),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) {
            const detail = d.errors ? Object.values(d.errors).flat().join(' ') : '';
            throw new Error(detail || d.message || ('Gagal (' + r.status + ')'));
        }
        return d;
    }

    /* ---------- mode kelola menu ---------- */
    function aturKelola(nyala) {
        kelola = nyala;
        $('kelola').setAttribute('aria-pressed', String(nyala));
        $('kelola').textContent = nyala ? 'Selesai' : 'Kelola';
        $('info-kelola').hidden = !nyala;
        grup = 'Semua'; kategori = 'Semua'; cari = ''; $('cari').value = ''; halaman = 1;
        renderKategori(); renderMenu();
    }
    $('kelola').addEventListener('click', () => aturKelola(!kelola));

    /* ---------- tambah dan ubah menu (hanya untuk outlet ini) ---------- */
    function isiKategoriForm(p) {
        const ada = {};
        OWN.products.forEach((x) => { if (x.category && x.category !== 'Parkir') ada[x.category] = true; });
        if (p && p.category) ada[p.category] = true;   // kategori menu yang sedang diubah selalu tersedia (termasuk Parkir)
        const kat = Object.keys(ada).sort((a, b) => urutan(a) - urutan(b) || a.localeCompare(b));

        $('m-kat').innerHTML = (p && !p.category ? '<option value="">(Tanpa kategori)</option>' : '')
            + kat.map((k) => '<option value="' + esc(k) + '">' + esc(k) + '</option>').join('')
            + '<option value="__baru">+ Kategori baru…</option>';

        if (p) $('m-kat').value = p.category || '';
        else $('m-kat').value = (kategori !== 'Semua' && kat.includes(kategori)) ? kategori : (kat[0] || '__baru');
    }

    function aturForm() {
        $('m-kat-baru-wrap').hidden = $('m-kat').value !== '__baru';
        $('m-stok-wrap').hidden = !$('m-track').checked;
        $('m-dapur-wrap').hidden = !KITCHEN;
        $('m-barcode-wrap').hidden = !BARCODE;
    }

    // p = menu yang diubah. Tanpa p = menambah menu baru.
    function bukaMenu(p) {
        editId = p ? p.id : null;
        $('form-menu').reset();
        $('m-pesan').textContent = '';
        isiKategoriForm(p);

        $('dlg-judul').textContent = (p ? 'Ubah menu' : 'Tambah menu baru') + ' · ' + OUTLET;
        $('l-hpp').textContent = p ? 'HPP (Rp), kosongkan = tidak diubah' : 'HPP (Rp), boleh kosong';
        $('m-hpp-note').textContent = p
            ? 'HPP yang sudah ada tidak berubah kalau kolom ini dikosongkan. Harga dan HPP baru hanya berlaku untuk penjualan berikutnya.'
            : 'Kalau HPP dikosongkan, sementara dihitung 40% dari harga jual. PIC bisa menggantinya dari dashboard.';
        $('l-stok').textContent = p ? 'Stok saat ini' : 'Stok awal';
        $('m-stok-note').hidden = !p;
        $('m-simpan').textContent = p ? 'Simpan perubahan' : 'Simpan menu';
        $('m-hapus').hidden = !p;

        if (p) {
            $('m-nama').value = p.name;
            $('m-harga').value = p.price;
            $('m-hpp').value = '';
            $('m-track').checked = !!p.track;
            $('m-stok').value = p.track ? p.stock : '';
            $('m-dapur').checked = !!p.kit;
            $('m-barcode').value = p.barcode || '';
        } else {
            $('m-track').checked = true;
            $('m-dapur').checked = true;
        }

        aturForm();
        $('dlg-menu').showModal();
        $('m-nama').focus();
    }

    function infoMenu(teks) {
        const el = $('info-menu');
        el.textContent = teks;
        el.hidden = false;
        clearTimeout(infoTimer);
        infoTimer = setTimeout(() => { el.hidden = true; }, 8000);
    }

    $('buka-menu').addEventListener('click', () => { if (kelola) aturKelola(false); bukaMenu(null); });
    $('m-batal').addEventListener('click', () => $('dlg-menu').close());
    $('dlg-menu').addEventListener('click', (e) => { if (e.target === $('dlg-menu')) $('dlg-menu').close(); });   // klik di luar jendela = tutup
    $('m-kat').addEventListener('change', () => { aturForm(); if ($('m-kat').value === '__baru') $('m-kat-baru').focus(); });
    $('m-track').addEventListener('change', aturForm);

    $('form-menu').addEventListener('submit', async (e) => {
        e.preventDefault();
        const salah = (t) => { $('m-pesan').textContent = t; };

        const nama = $('m-nama').value.trim().replace(/\s+/g, ' ');
        const harga = Number($('m-harga').value);
        const hppTeks = $('m-hpp').value.trim();
        const hpp = hppTeks === '' ? null : Number(hppTeks);
        const track = $('m-track').checked;
        const stokTeks = $('m-stok').value.trim();
        const katBaru = $('m-kat').value === '__baru';
        const kat = katBaru ? $('m-kat-baru').value.trim().replace(/\s+/g, ' ') : $('m-kat').value;

        if (!nama) return salah('Isi nama menu.');
        if ($('m-harga').value.trim() === '' || !Number.isInteger(harga) || harga < 1) return salah('Harga jual minimal Rp 1 dan harus angka bulat.');
        if (hpp !== null && (!Number.isInteger(hpp) || hpp < 0)) return salah('HPP harus angka bulat 0 atau lebih.');
        if (katBaru && !kat) return salah('Isi nama kategori baru.');
        if (track && (stokTeks === '' || !Number.isInteger(Number(stokTeks)) || Number(stokTeks) < 0)) return salah('Isi stok (angka 0 atau lebih), atau matikan "Pakai stok".');

        $('m-pesan').textContent = '';
        $('m-simpan').disabled = true;

        const body = {
            name: nama,
            price: harga,
            cost: hpp,
            category: kat || null,
            stock: track ? Number(stokTeks) : 0,
            track_stock: track,
            to_kitchen: KITCHEN ? $('m-dapur').checked : false,
            barcode: BARCODE ? $('m-barcode').value.trim() : null,
        };

        try {
            if (editId) {
                // ---- ubah menu ----
                const d = await kirim(URL_MENU_BASE + '/' + editId, body, 'PUT');
                const p = produk(editId);
                const sold = p.sold;
                Object.assign(p, d.product, { outlet: p.outlet, sold: sold });

                renderKategori();
                renderCart();   // harga di keranjang ikut diperbarui; renderCart juga menggambar ulang daftar menu
                $('dlg-menu').close();
                infoMenu('Menu "' + p.name + '" diperbarui.');
            } else {
                // ---- tambah menu ----
                const d = await kirim(URL_MENU, body);
                const baru = d.product;
                baru.outlet = OWN.outlet;
                OWN.products.push(baru);
                SEMUA.push(baru);

                // Tampilkan menu baru: buka kelompok dan kategorinya, lalu loncat ke halaman tempat menu itu berada.
                aktif = OWN.outlet;
                kategori = baru.category || 'Semua';
                grup = baru.category && grupDari(baru.category) !== 'Lainnya' ? grupDari(baru.category) : 'Semua';
                cari = ''; $('cari').value = '';
                renderKatalog(); renderKategori();
                const posisi = terfilter().findIndex((p) => p.id === baru.id);
                halaman = posisi >= 0 ? Math.floor(posisi / PER_HALAMAN) + 1 : 1;
                renderMenu();

                $('dlg-menu').close();
                infoMenu('Menu "' + baru.name + '" ditambahkan di ' + OUTLET + '.'
                    + (d.hpp_sementara ? ' HPP diisi sementara 40% dari harga jual, PIC bisa menggantinya.' : ''));
            }
        } catch (err) {
            salah(err.message);
        }
        $('m-simpan').disabled = false;
    });

    // ---- hapus menu ----
    $('m-hapus').addEventListener('click', async () => {
        const p = editId ? produk(editId) : null;
        if (!p) return;
        if (!confirm('Hapus menu "' + p.name + '" dari ' + OUTLET + '?\n\nMenu ini hilang dari kasir dan halaman customer. Riwayat penjualan tidak berubah.')) return;

        $('m-hapus').disabled = true;
        try {
            await kirim(URL_MENU_BASE + '/' + p.id, null, 'DELETE');

            const buang = (arr) => { const i = arr.findIndex((x) => x.id === p.id); if (i >= 0) arr.splice(i, 1); };
            buang(OWN.products); buang(SEMUA);
            delete cart[p.id];

            renderKategori();
            renderCart();
            $('dlg-menu').close();
            infoMenu('Menu "' + p.name + '" dihapus.');
        } catch (err) {
            $('m-pesan').textContent = err.message;
        }
        $('m-hapus').disabled = false;
    });

    /* ---------- pembayaran ---------- */
    $('bayar').addEventListener('click', async () => {
        sending = true; $('bayar').disabled = true; pesan('Mengirim…', true);
        try {
            const nilai = Number($('d-nilai').value) || 0;
            const d = await kirim(URL_STORE, {
                table: TABLES > 0 ? Number($('meja').value) : null,
                method: metode,
                paid: metode === 'tunai' ? Number($('paid').value) : null,
                items: Object.entries(cart).map(([id, qty]) => ({ id: Number(id), qty: qty })),
                discount_type: nilai > 0 ? $('d-tipe').value : null,
                discount_value: nilai > 0 ? nilai : null,
                discount_note: nilai > 0 ? ($('d-note').value.trim() || null) : null,
            });

            const dapur = d.orders.filter((o) => o.kitchen_status === 'antrian').map((o) => o.outlet);
            pesan('Berhasil. Pesanan ' + d.orders.map((o) => '#' + o.id).join(', ') + ' sudah dibayar.'
                + (dapur.length ? ' Sudah dikirim ke dapur ' + Array.from(new Set(dapur)).join(' dan ') + '.' : '')
                + (metode === 'tunai' ? ' Kembalian ' + rp(d.bill.change) + '.' : ''), true);

            cetakStruk(d);
            cart = {}; $('paid').value = ''; kosongkanDiskon(); renderCart(); await muat();
        } catch (e) { pesan(e.message, false); }
        sending = false; hitungKembali();
    });

    function cetakStruk(d) {
        const w = window.open('', '_blank', 'width=360,height=640');
        if (!w) { pesan('Pesanan tersimpan, tetapi pop-up struk diblokir. Izinkan pop-up untuk mencetak struk.', false); return; }

        const b = d.bill;
        const baris = (a, c) => '<div class="r"><span>' + a + '</span><span>' + c + '</span></div>';
        const gabungan = d.orders.length > 1;

        const isi = d.orders.map((o) =>
            (gabungan ? '<div><b>' + esc(o.outlet) + '</b> · ' + esc(o.table) + '</div>' : '')
            + o.items.map((i) => '<div>' + esc(i.name) + '</div>' + baris(i.qty + ' x ' + rp(i.price), rp(i.subtotal))).join('')
        ).join('<hr>');

        const sv = d.orders.find((o) => o.service_rate > 0);
        const nomor = d.orders.map((o) => '#' + o.id).join(' ') + (gabungan ? '' : ' · ' + esc(d.orders[0].table));
        const doc = w.document;
        doc.open();
        doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Struk ' + esc(nomor) + '</title>'
            + '<style>body{font:13px monospace;width:280px;margin:8px}h3{margin:0;text-align:center}hr{border:0;border-top:1px dashed #000}.r{display:flex;justify-content:space-between}</style></head><body>'
            + '<h3>' + esc(OUTLET) + '</h3><div style="text-align:center">' + new Date().toLocaleString('id-ID') + '</div><hr>'
            + '<div>No: ' + nomor + '</div><hr>'
            + isi + '<hr>'
            + baris('Subtotal', rp(b.subtotal))
            + (b.discount > 0 ? baris('Diskon' + (b.discount_note ? ' (' + esc(b.discount_note) + ')' : ''), '-' + rp(b.discount)) : '')
            + (b.service > 0 ? baris('Service' + (sv ? ' ' + sv.service_rate + '%' : ''), rp(b.service)) : '')
            + '<div class="r"><b>TOTAL</b><b>' + rp(b.total) + '</b></div>'
            + baris('Bayar (' + esc(b.method) + ')', rp(b.paid))
            + baris('Kembali', rp(b.change)) + '<hr>'
            + '<div style="text-align:center">Terima kasih</div></body></html>');
        doc.close();
        setTimeout(() => { w.focus(); w.print(); }, 250);
    }

    /* ---------- pesanan customer yang menunggu dibayar ---------- */
    function renderPending(orders) {
        const list = orders.filter((o) => o.status === 'menunggu_bayar');
        $('box-pending').hidden = list.length === 0;
        $('n-pending').textContent = list.length;
        $('pending').innerHTML = list.map((o) => {
            const cara = o.pay_preference === 'meja'
                ? '<span class="tag warn">Memilih QRIS, belum dibayar</span>'
                : '<span class="tag">Bayar di kasir</span>';
            const service = o.service > 0 ? ' · termasuk service ' + rp(o.service) : '';
            return `<div class="pend"><div class="row"><b>#${o.id} · ${esc(o.table)} ${cara}</b><b>${rp(o.grand_total)}</b></div>
             <div class="muted">${esc(o.items.map((i) => i.qty + 'x ' + i.name).join(', '))}${service}</div>
             <div class="row" style="margin-top:8px;justify-content:flex-start;flex-wrap:wrap">
                <button class="small" data-bayar="${o.id}" data-m="tunai">Terima tunai (pas)</button>
                <button class="small" data-bayar="${o.id}" data-m="qris">Terima QRIS</button>
                <button class="small sec" data-batal="${o.id}">Batalkan</button></div></div>`;
        }).join('');
    }
    $('pending').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-bayar]'), x = e.target.closest('[data-batal]');
        try {
            if (b) {
                const o = (window.__orders || []).find((q) => q.id === Number(b.dataset.bayar));
                await kirim(URL_ORDER + '/' + b.dataset.bayar + '/bayar', { method: b.dataset.m, paid: b.dataset.m === 'tunai' ? o.grand_total : null });
                pesan('Pembayaran pesanan #' + b.dataset.bayar + ' diterima.', true);
            }
            if (x) {
                if (!confirm('Batalkan pesanan #' + x.dataset.batal + '? Stok akan dikembalikan.')) return;
                await kirim(URL_ORDER + '/' + x.dataset.batal + '/batal');
                pesan('Pesanan dibatalkan.', true);
            }
            await muat();
        } catch (err) { pesan(err.message, false); }
    });

    /* ---------- stok, terlaris, dan pesanan terbaru ---------- */
    async function muat() {
        try {
            const r = await fetch(URL_DATA, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (r.status === 401 || r.status === 419) { location.reload(); return; }
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();
            window.__orders = d.orders;

            d.products.concat(d.linked_stock || []).forEach((s) => { const p = produk(s.id); if (p && p.track) p.stock = s.stock; });
            if (d.sold) OWN.products.forEach((p) => { p.sold = Number(d.sold[p.id] || 0); });

            Object.keys(cart).forEach((id) => {
                const p = produk(Number(id));
                if (!p) { delete cart[id]; return; }
                if (p.track && cart[id] > p.stock) cart[id] = Math.max(0, p.stock);
                if (!cart[id]) delete cart[id];
            });

            cekPesananBaru(d.orders);
            renderPending(d.orders); renderCart();
            $('status').textContent = '● Real-time · ' + new Date().toLocaleTimeString('id-ID'); $('status').className = 'live';
        } catch (e) { $('status').textContent = 'Gagal memuat data, mencoba lagi…'; $('status').className = 'live err'; }
    }

    renderKatalog(); renderKategori(); renderParkir(); renderCart(); muat(); setInterval(muat, SEGARKAN_MS);
})();
