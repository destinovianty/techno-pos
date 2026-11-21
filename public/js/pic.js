(function () {
    const CFG = window.PIC;
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const BATAS_MENIPIS = 5;

    // Semua data ini diisi dari database lewat /pic/data
    let OUTLETS = [];          // [{nama, jual, hpp, trx}] penjualan hari ini, urutan tetap
    let STOK = {};             // {produk: [stok per outlet; null = outlet itu tidak menjual produknya]}
    let PRODUK = {};           // {produk: {hpp, harga, jasa?}}
    let SERVER_LAPORAN = {};   // {harian|mingguan|bulanan: [{nama, jual, hpp, trx}]}
    let PERJAM = {};           // {hari|rata7: [{nama, jual:[24], trx:[24]}]}
    let TREN = [];             // penjualan 7 hari terakhir, juta rupiah
    let LARIS = [];            // [[nama, jumlah]]
    let SUPPLIERS = [];        // [{id, nama, ket, wa}]
    let PRODUK_SUPPLIER = {};  // {produk: id supplier}
    let PESANAN = [];          // pesanan ke supplier
    let TRX = [];              // transaksi terakhir

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const rp = (n) => (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(Math.round(n)).toLocaleString('id-ID');
    const num = (n) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 1 });
    const pct = (n) => n.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';
    const hitungMargin = (hpp, jual) => (jual > 0 ? ((jual - hpp) / jual) * 100 : 0);
    const ada = (v) => v !== null && v !== undefined;
    const produkList = () => Object.keys(STOK);
    const menipisDi = (i) => produkList().filter((p) => ada(STOK[p][i]) && STOK[p][i] <= BATAS_MENIPIS).length;
    const supplierById = (id) => SUPPLIERS.find((s) => s.id === Number(id));
    const tampil = (el, teks, ok) => { el.textContent = teks; el.className = 'pesan ' + (ok ? 'good' : 'err'); };

    let periodeAktif = 'harian';
    let rentangJam = 'hari';
    let KAP = 5;    // transaksi per jam yang sanggup dilayani 1 karyawan (perkiraan, ubah lewat kolom)
    let STAF = 1;   // jumlah karyawan yang bertugas (ubah lewat kolom)
    try {
        const s = JSON.parse(localStorage.getItem('pic_staf') || 'null');
        if (s && s.kap >= 1 && s.staf >= 1) { KAP = s.kap; STAF = s.staf; }
    } catch (e) { /* penyimpanan browser tidak tersedia, pakai nilai awal */ }
    $('pj-kap').value = KAP;
    $('pj-staf').value = STAF;

    /* ---------- komunikasi dengan server ---------- */
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
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body || {}),
    });

    function setStatus(teks, live) {
        const el = $('status-data');
        el.textContent = teks;
        el.classList.toggle('live', live);
    }

    /* ---------- DASBOR ---------- */
    function renderDasbor() {
        const jual = OUTLETS.reduce((a, o) => a + o.jual, 0);
        const hpp = OUTLETS.reduce((a, o) => a + o.hpp, 0);
        const trx = OUTLETS.reduce((a, o) => a + o.trx, 0);
        const low = OUTLETS.reduce((a, _, i) => a + menipisDi(i), 0);
        $('k-jual').textContent = rp(jual);
        $('k-trx').textContent = trx;
        $('k-laba').textContent = rp(jual - hpp);
        $('k-low').textContent = low + ' item';

        const maks = Math.max(1, ...TREN);
        const hari = TREN.map((_, i) => {
            const d = new Date();
            d.setDate(d.getDate() - (TREN.length - 1 - i));
            return d.toLocaleDateString('id-ID', { weekday: 'short' });
        });
        $('tren').innerHTML = TREN.map((v, i) =>
            `<div class="bar-col"><span>${v}</span><div class="bar${i === TREN.length - 1 ? ' now' : ''}" style="height:${Math.max(2, (v / maks) * 100)}%"></div><span>${hari[i]}</span></div>`
        ).join('');

        const mk = Math.max(1, ...LARIS.map((x) => x[1]));
        $('laris').innerHTML = LARIS.length
            ? LARIS.map(([n, q]) => `<div class="hbar"><span>${esc(n)}</span><div class="track"><div class="fill" style="width:${(q / mk) * 100}%"></div></div><span class="num">${q}</span></div>`).join('')
            : '<p class="empty">Belum ada penjualan.</p>';

        $('banding').innerHTML = OUTLETS.map((o, i) => {
            const m = menipisDi(i);
            return `<tr><td>${esc(o.nama)}</td><td>${rp(o.jual)}</td><td class="ok">${rp(o.jual - o.hpp)}</td><td>${o.trx}</td><td class="${m ? 'low' : 'ok'}">${m}</td></tr>`;
        }).join('');
    }

    /* ---------- JAM RAMAI (PENDAPATAN PER JAM) ---------- */
    const labelJam = (h) => String(h).padStart(2, '0') + ':00';
    const rpSingkat = (n) => (n >= 1000000 ? (n / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt' : Math.round(n / 1000).toLocaleString('id-ID') + ' rb');
    const dataJam = (rentang) => (PERJAM && PERJAM[rentang]) || [];

    function renderJam() {
        const f = $('pj-outlet').value;
        const semua = dataJam(rentangJam);
        const rows = f === 'semua' ? semua : [semua[Number(f)]].filter(Boolean);
        const satuOutlet = f !== 'semua';

        const jual = Array(24).fill(0), trx = Array(24).fill(0);
        rows.forEach((r) => { for (let h = 0; h < 24; h++) { jual[h] += Number(r.jual[h]) || 0; trx[h] += Number(r.trx[h]) || 0; } });

        const aktif = [];
        for (let h = 0; h < 24; h++) if (jual[h] > 0 || trx[h] > 0) aktif.push(h);

        $('pj-kosong').hidden = aktif.length > 0;
        if (!aktif.length) {
            ['pj-puncak', 'pj-omzet', 'pj-ramai', 'pj-tambah'].forEach((id) => { $(id).textContent = '-'; });
            $('pj-chart').innerHTML = ''; $('pj-body').innerHTML = ''; $('pj-legend').innerHTML = ''; $('pj-note').textContent = '';
            return;
        }

        const butuh = (h) => Math.ceil(trx[h] / KAP);
        const tambah = (h) => (satuOutlet ? Math.max(0, butuh(h) - STAF) : 0);
        const urut = [...aktif].sort((a, b) => jual[b] - jual[a]);
        const top3 = new Set(urut.slice(0, 3));
        const ramai = (h) => (satuOutlet ? tambah(h) > 0 : top3.has(h));

        const jamRamai = aktif.filter(ramai);
        const maksTambah = satuOutlet ? Math.max(0, ...aktif.map(tambah)) : null;

        $('pj-puncak').textContent = labelJam(urut[0]) + '–' + labelJam((urut[0] + 1) % 24);
        $('pj-omzet').textContent = rp(jual[urut[0]]);
        $('pj-ramai').textContent = satuOutlet ? jamRamai.length + ' jam' : '3 jam teratas';
        $('pj-tambah').textContent = satuOutlet ? (maksTambah > 0 ? '+' + maksTambah + ' orang' : 'Tidak perlu') : 'Pilih 1 outlet';

        $('pj-legend').innerHTML = satuOutlet
            ? '<span><i style="background:var(--primary)"></i>Jam ramai (butuh tambahan karyawan)</span><span><i style="background:var(--primary-tint)"></i>Jam normal</span>'
            : '<span><i style="background:var(--primary)"></i>3 jam dengan pendapatan tertinggi</span><span><i style="background:var(--primary-tint)"></i>Jam lain</span>';

        const maks = Math.max(...aktif.map((h) => jual[h]), 1);
        $('pj-chart').style.minWidth = (aktif.length * 34) + 'px';
        $('pj-chart').innerHTML = aktif.map((h) =>
            `<div class="bar-col"><span>${rpSingkat(jual[h])}</span><div class="bar${ramai(h) ? ' now' : ''}" style="height:${Math.max(3, (jual[h] / maks) * 100)}%"></div><span>${String(h).padStart(2, '0')}</span></div>`
        ).join('');

        $('pj-body').innerHTML = aktif.map((h) => {
            const saran = satuOutlet
                ? (tambah(h) > 0 ? `<span class="badge pending">+${tambah(h)} karyawan</span>` : '<span class="badge done">Cukup</span>')
                : (top3.has(h) ? '<span class="badge pending">Jam puncak</span>' : '');
            return `<tr class="${ramai(h) ? 'ramai' : ''}"><td>${labelJam(h)}–${labelJam((h + 1) % 24)}</td><td>${rp(jual[h])}</td><td>${num(trx[h])}</td><td>${satuOutlet ? butuh(h) + ' orang' : '-'}</td><td>${saran}</td></tr>`;
        }).join('');

        $('pj-note').textContent = satuOutlet
            ? `Kebutuhan karyawan = jumlah transaksi per jam ÷ ${KAP} (kapasitas 1 karyawan), dibulatkan ke atas, lalu dibandingkan dengan ${STAF} karyawan yang bertugas. Kedua angka itu perkiraan, sesuaikan di kolom atas dengan kecepatan layanan outlet.`
            : 'Pilih satu outlet untuk melihat saran tambahan karyawan per jam. Untuk semua outlet, yang ditandai adalah 3 jam dengan pendapatan tertinggi.';
    }

    document.querySelectorAll('[data-rentang]').forEach((b) => b.addEventListener('click', () => {
        rentangJam = b.dataset.rentang;
        document.querySelectorAll('[data-rentang]').forEach((x) => { x.className = x === b ? 'btn' : 'btn secondary'; });
        renderJam();
    }));
    $('pj-outlet').addEventListener('change', renderJam);
    ['pj-kap', 'pj-staf'].forEach((id) => $(id).addEventListener('input', () => {
        const k = Number($('pj-kap').value), s = Number($('pj-staf').value);
        if (!Number.isInteger(k) || k < 1 || !Number.isInteger(s) || s < 1) return;
        KAP = k; STAF = s;
        try { localStorage.setItem('pic_staf', JSON.stringify({ kap: KAP, staf: STAF })); } catch (e) { /* abaikan */ }
        renderJam();
    }));

    /* ---------- STOK ---------- */
    function renderStok() {
        $('stok-head').innerHTML = '<tr><th>Produk</th>' + OUTLETS.map((o) => `<th>${esc(o.nama)}</th>`).join('') + '<th>Total</th></tr>';
        $('stok-body').innerHTML = produkList().map((p) => {
            const cells = STOK[p].map((n) => (ada(n) ? `<td class="${n <= BATAS_MENIPIS ? 'low' : ''}">${n}</td>` : '<td>-</td>')).join('');
            return `<tr><td>${esc(p)}</td>${cells}<td>${STOK[p].reduce((a, b) => a + (b || 0), 0)}</td></tr>`;
        }).join('');
    }

    /* ---------- PRODUK & HARGA ---------- */
    function stokProduk(p, f) {
        const s = STOK[p];
        if (!s) return { teks: 'Tanpa stok', low: false };
        if (f === 'semua') {
            const nilai = s.filter(ada);
            return { teks: nilai.reduce((a, b) => a + b, 0), low: nilai.length > 0 && Math.min(...nilai) <= BATAS_MENIPIS };
        }
        const v = s[Number(f)];
        return ada(v) ? { teks: v, low: v <= BATAS_MENIPIS } : { teks: '-', low: false };
    }

    function renderHarga() {
        const f = $('f-outlet-harga').value || 'semua';
        $('harga-body').innerHTML = Object.keys(PRODUK).map((p) => {
            const d = PRODUK[p];
            const lb = d.harga - d.hpp, m = hitungMargin(d.hpp, d.harga), rugi = lb < 0;
            const st = stokProduk(p, f);
            return `<tr><td>${esc(p)}</td><td>${rp(d.hpp)}</td><td>${rp(d.harga)}</td><td>${rp(lb)}</td>
                <td class="${rugi || m < 30 ? 'low' : 'ok'}">${pct(m)}${rugi ? ' <span class="badge pending">Rugi</span>' : ''}</td>
                <td class="${st.low ? 'low' : ''}">${st.teks}</td>
                <td><button class="linkbtn" data-ubah="${esc(p)}">Ubah</button></td></tr>`;
        }).join('');
    }

    function isiHarga() {
        const d = PRODUK[$('h-produk').value] || { hpp: 0, harga: 0 };
        $('h-hpp').value = d.hpp;
        $('h-jual').value = d.harga;
    }

    $('harga-body').addEventListener('click', (e) => {
        const b = e.target.closest('[data-ubah]'); if (!b) return;
        $('h-produk').value = b.dataset.ubah; isiHarga(); $('h-hpp').focus();
    });
    $('h-produk').addEventListener('change', isiHarga);
    $('f-outlet-harga').addEventListener('change', renderHarga);

    $('form-harga').addEventListener('submit', async (e) => {
        e.preventDefault();
        const p = $('h-produk').value, hpp = Number($('h-hpp').value), jual = Number($('h-jual').value), pesan = $('pesan-harga');
        if (!p) return tampil(pesan, 'Pilih produk dulu.', false);
        if (!Number.isInteger(hpp) || hpp < 0) return tampil(pesan, 'HPP harus berupa angka 0 atau lebih.', false);
        if (!Number.isInteger(jual) || jual < 1) return tampil(pesan, 'Harga jual minimal Rp 1.', false);
        $('btn-harga').disabled = true;
        try {
            await post(CFG.urlProduk, { nama: p, hpp: hpp, harga: jual });
            await segarkan();
            if (jual < hpp) tampil(pesan, `Tersimpan, tetapi harga jual di bawah HPP. ${p} akan rugi ${rp(hpp - jual)} per unit.`, false);
            else tampil(pesan, `${p}: HPP ${rp(hpp)}, harga jual ${rp(jual)}, margin ${pct(hitungMargin(hpp, jual))}. Tersimpan di semua outlet yang menjualnya.`, true);
        } catch (err) { tampil(pesan, err.message, false); }
        $('btn-harga').disabled = false;
    });

    /* ---------- LAPORAN LABA ---------- */
    const laporanData = (periode) => (SERVER_LAPORAN && SERVER_LAPORAN[periode]) || [];

    function renderLaba() {
        const semua = laporanData(periodeAktif);
        const f = $('l-outlet').value;
        const rows = f === 'semua' || f === '' ? semua : [semua[Number(f)]].filter(Boolean);
        const jual = rows.reduce((a, r) => a + r.jual, 0), hpp = rows.reduce((a, r) => a + r.hpp, 0), laba = jual - hpp;
        $('l-jual').textContent = rp(jual);
        $('l-hpp').textContent = rp(hpp);
        $('l-laba').textContent = rp(laba);
        $('l-margin').textContent = pct(jual > 0 ? (laba / jual) * 100 : 0);
        $('l-judul').textContent = 'Laba per Outlet · ' + { harian: 'Hari ini', mingguan: '7 hari terakhir', bulanan: '30 hari terakhir' }[periodeAktif];

        $('l-body').innerHTML = rows.map((r) =>
            `<tr><td>${esc(r.nama)}</td><td>${rp(r.jual)}</td><td>${rp(r.hpp)}</td><td class="ok">${rp(r.jual - r.hpp)}</td><td>${pct(r.jual > 0 ? ((r.jual - r.hpp) / r.jual) * 100 : 0)}</td></tr>`
        ).join('') + (rows.length > 1 ? `<tr class="total"><td>Total</td><td>${rp(jual)}</td><td>${rp(hpp)}</td><td>${rp(laba)}</td><td>${pct(jual > 0 ? (laba / jual) * 100 : 0)}</td></tr>` : '');

        const mk = Math.max(1, ...rows.map((r) => r.jual - r.hpp));
        $('l-chart').innerHTML = rows.map((r) =>
            `<div class="hbar"><span>${esc(r.nama)}</span><div class="track"><div class="fill green" style="width:${Math.max(0, ((r.jual - r.hpp) / mk) * 100)}%"></div></div><span class="num">${rp(r.jual - r.hpp)}</span></div>`
        ).join('');
    }

    document.querySelectorAll('[data-periode]').forEach((b) => b.addEventListener('click', () => {
        periodeAktif = b.dataset.periode;
        document.querySelectorAll('[data-periode]').forEach((x) => { x.className = x === b ? 'btn' : 'btn secondary'; });
        renderLaba();
    }));
    $('l-outlet').addEventListener('change', renderLaba);

    /* ---------- ISI PILIHAN (SELECT) ---------- */
    function isiSelect(el, html) {
        const v = el.value;
        el.innerHTML = html;
        if (Array.from(el.options).some((o) => o.value === v)) el.value = v;
    }

    let sigForm = '';
    function isiForm() {
        if (!OUTLETS.length) return;
        // Pilihan hanya dibangun ulang kalau daftarnya berubah, supaya pilihan yang sedang dibuka tidak tertutup.
        const sig = JSON.stringify([OUTLETS.map((o) => o.nama), Object.keys(STOK), Object.keys(PRODUK), SUPPLIERS.map((s) => s.id + s.nama)]);
        if (sig === sigForm) return;
        const pertama = sigForm === '';
        sigForm = sig;

        const opt = OUTLETS.map((o, i) => `<option value="${i}">${esc(o.nama)}</option>`).join('');
        const prodStok = produkList().map((p) => `<option>${esc(p)}</option>`).join('');
        const prodSemua = Object.keys(PRODUK).map((p) => `<option>${esc(p)}</option>`).join('');

        isiSelect($('dari'), opt);
        isiSelect($('ke'), opt);
        isiSelect($('produk'), prodStok);
        isiSelect($('po-outlet'), opt);
        isiSelect($('po-produk'), prodStok);
        isiSelect($('po-supplier'), SUPPLIERS.map((s) => `<option value="${s.id}">${esc(s.nama)}${s.ket ? ' · ' + esc(s.ket) : ''}</option>`).join(''));
        isiSelect($('h-produk'), prodSemua);
        isiSelect($('f-outlet-harga'), '<option value="semua">Semua outlet (total)</option>' + opt);
        isiSelect($('l-outlet'), '<option value="semua">Semua outlet</option>' + opt);
        isiSelect($('pj-outlet'), '<option value="semua">Semua outlet</option>' + opt);

        if (pertama) {
            $('ke').value = OUTLETS.length > 1 ? '1' : '0';
            $('pj-outlet').value = '0';
            pilihSupplierOtomatis();
            isiHarga();
        }
    }

    /* ---------- SUPPLIER ---------- */
    function pilihSupplierOtomatis() {
        const id = PRODUK_SUPPLIER[$('po-produk').value];
        if (id && supplierById(id)) $('po-supplier').value = String(id);
    }

    function renderSupplier() {
        const perlu = [];
        OUTLETS.forEach((o, i) => produkList().forEach((p) => {
            const v = STOK[p][i];
            if (!ada(v) || v > BATAS_MENIPIS) return;
            const sudah = PESANAN.some((x) => x.outlet === o.nama && x.produk === p && x.status === 'Dipesan');
            if (!sudah) perlu.push({ i, p, v });
        }));

        $('perlu-body').innerHTML = perlu.map(({ i, p, v }) => {
            const s = supplierById(PRODUK_SUPPLIER[p]);
            return `<tr><td>${esc(OUTLETS[i].nama)}</td><td>${esc(p)}</td><td class="low">${v}</td><td>${s ? esc(s.nama) : '-'}</td>
                <td><button class="btn small" data-pesan="${i}|${esc(p)}">Pesan</button></td></tr>`;
        }).join('');
        $('perlu-kosong').hidden = perlu.length > 0;

        $('po-body').innerHTML = PESANAN.length ? PESANAN.map((x) => {
            const s = supplierById(x.supplier);
            return `<tr><td>${esc(x.no)}</td><td>${s ? esc(s.nama) : '-'}</td><td>${esc(x.outlet)}</td><td>${esc(x.produk)}</td><td>${x.qty}</td>
                <td><span class="badge ${x.status === 'Diterima' ? 'done' : 'pending'}">${x.status}</span></td>
                <td>${x.status === 'Dipesan' ? `<button class="linkbtn" data-terima="${x.id}">Tandai diterima</button>` : ''}</td></tr>`;
        }).join('') : '<tr><td colspan="7">Belum ada pesanan ke supplier.</td></tr>';
    }

    $('perlu-body').addEventListener('click', (e) => {
        const b = e.target.closest('[data-pesan]'); if (!b) return;
        const sekat = b.dataset.pesan.indexOf('|');
        $('po-outlet').value = b.dataset.pesan.slice(0, sekat);
        $('po-produk').value = b.dataset.pesan.slice(sekat + 1);
        pilihSupplierOtomatis();
        $('po-jumlah').value = 20;
        $('po-jumlah').focus();
    });

    $('po-body').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-terima]'); if (!b) return;
        b.disabled = true;
        try {
            await post(CFG.urlTerima + '/' + b.dataset.terima + '/terima');
            await segarkan();
        } catch (err) {
            alert(err.message);
            b.disabled = false;
        }
    });

    function bacaFormPO() {
        const idx = Number($('po-outlet').value);
        return {
            supplier: Number($('po-supplier').value),
            outlet: OUTLETS[idx] ? OUTLETS[idx].nama : '',
            produk: $('po-produk').value,
            qty: Number($('po-jumlah').value),
            catatan: $('po-catatan').value.trim(),
        };
    }

    $('po-produk').addEventListener('change', pilihSupplierOtomatis);

    $('form-po').addEventListener('submit', async (e) => {
        e.preventDefault();
        const d = bacaFormPO(), pesan = $('pesan-po');
        if (!d.supplier || !d.outlet || !d.produk) return tampil(pesan, 'Lengkapi supplier, outlet, dan produk.', false);
        if (!Number.isInteger(d.qty) || d.qty < 1) return tampil(pesan, 'Jumlah minimal 1.', false);
        $('btn-po').disabled = true;
        try {
            const r = await post(CFG.urlPesanan, { supplier_id: d.supplier, outlet: d.outlet, produk: d.produk, jumlah: d.qty, catatan: d.catatan });
            await segarkan();
            const s = supplierById(d.supplier);
            tampil(pesan, `${r.kode} tercatat: ${d.qty} ${d.produk} dari ${s ? s.nama : 'supplier'} untuk ${d.outlet}.`, true);
        } catch (err) { tampil(pesan, err.message, false); }
        $('btn-po').disabled = false;
    });

    $('po-wa').addEventListener('click', () => {
        const d = bacaFormPO(), s = supplierById(d.supplier);
        if (!s) return tampil($('pesan-po'), 'Pilih supplier dulu.', false);
        if (!Number.isInteger(d.qty) || d.qty < 1) return tampil($('pesan-po'), 'Jumlah minimal 1.', false);
        const teks = `Halo ${s.nama}, kami ingin memesan ${d.qty} ${d.produk} untuk outlet ${d.outlet}.` + (d.catatan ? ` Catatan: ${d.catatan}.` : '') + ' Terima kasih.';
        window.open('https://wa.me/' + s.wa + '?text=' + encodeURIComponent(teks), '_blank', 'noopener');
    });

    /* ---------- TRANSAKSI ---------- */
    const BUKA = new Set();

    function renderTrx() {
        $('trx-body').innerHTML = TRX.length ? TRX.map((t) => {
            const buka = BUKA.has(t.no);
            return `<tr><td>${esc(t.no)}</td><td>${esc(t.outlet)}</td><td>${esc(t.jam)}</td><td>${esc(t.metode)}</td><td>${rp(t.total)}</td>
                <td><button class="linkbtn" data-no="${esc(t.no)}" aria-expanded="${buka}">Lihat struk</button></td></tr>
                <tr class="detail"${buka ? '' : ' hidden'}><td colspan="6">${esc(t.isi)}</td></tr>`;
        }).join('') : '<tr><td colspan="6">Belum ada transaksi.</td></tr>';
    }

    $('trx-body').addEventListener('click', (e) => {
        const b = e.target.closest('[data-no]'); if (!b) return;
        const no = b.dataset.no;
        if (BUKA.has(no)) BUKA.delete(no); else BUKA.add(no);
        renderTrx();
    });

    /* ---------- TRANSFER ---------- */
    $('form-transfer').addEventListener('submit', async (e) => {
        e.preventDefault();
        const dari = OUTLETS[Number($('dari').value)], ke = OUTLETS[Number($('ke').value)];
        const p = $('produk').value, qty = Number($('jumlah').value), pesan = $('pesan-transfer');
        if (!dari || !ke || !p) return tampil(pesan, 'Lengkapi outlet asal, outlet tujuan, dan produk.', false);
        if (dari.nama === ke.nama) return tampil(pesan, 'Outlet asal dan tujuan tidak boleh sama.', false);
        if (!Number.isInteger(qty) || qty < 1) return tampil(pesan, 'Jumlah minimal 1.', false);
        $('btn-transfer').disabled = true;
        try {
            await post(CFG.urlTransfer, { dari: dari.nama, ke: ke.nama, produk: p, jumlah: qty });
            await segarkan();
            tampil(pesan, `Berhasil: ${qty} ${p} dari ${dari.nama} ke ${ke.nama}.`, true);
        } catch (err) { tampil(pesan, err.message, false); }
        $('btn-transfer').disabled = false;
    });

    /* ---------- TAB ---------- */
    const PANELS = ['dasbor', 'jam', 'stok', 'harga', 'laba', 'supplier', 'transaksi'];
    document.querySelectorAll('[role=tab]').forEach((tab) => tab.addEventListener('click', () => {
        document.querySelectorAll('[role=tab]').forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
        PANELS.forEach((id) => { $(id).hidden = id !== tab.dataset.panel; });
    }));

    /* ---------- AMBIL DATA DARI DATABASE ---------- */
    function renderSemua() { renderDasbor(); renderJam(); renderStok(); renderHarga(); renderLaba(); renderSupplier(); renderTrx(); }

    async function segarkan() {
        try {
            const d = await api(CFG.urlData, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            OUTLETS = d.outlets || [];
            STOK = d.stok || {};
            PRODUK = d.produk || {};
            SERVER_LAPORAN = d.laporan || {};
            PERJAM = d.perjam || {};
            TREN = d.tren || [];
            LARIS = d.laris || [];
            SUPPLIERS = d.suppliers || [];
            PRODUK_SUPPLIER = d.produk_supplier || {};
            PESANAN = d.pesanan || [];
            TRX = d.trx || [];
            isiForm();
            renderSemua();
            setStatus('● Real-time · ' + new Date().toLocaleTimeString('id-ID'), true);
        } catch (err) {
            if (err.status === 401 || err.status === 419) { location.reload(); return; }
            setStatus(err.status === 403 ? 'Akses ditolak (khusus PIC)' : 'Gagal memuat, mencoba lagi…', false);
        }
    }

    segarkan();
    setInterval(segarkan, 10000);
})();
