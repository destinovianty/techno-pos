(function () {
    const CFG = window.DAPUR;
    const URL_DATA = CFG.urlData;
    const URL_MAJU = CFG.urlMaju;
    const OUTLET = CFG.outlet;
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    // Batas waktu (detik) sebelum timer berubah merah. Ubah sesuai kebutuhan dapur.
    const TAHAP = {
        antrian: { label: 'Antrian', warna: '#2563EB', tombol: 'Ambil pesanan', mulai: (o) => o.created_at, batas: 300 },
        diambil: { label: 'Diambil', warna: '#B45309', tombol: 'Mulai masak',   mulai: (o) => o.taken_at,   batas: 300 },
        dimasak: { label: 'Dimasak', warna: '#C2410C', tombol: 'Siap serving',  mulai: (o) => o.cooking_at, batas: 600 },
        serving: { label: 'Serving', warna: '#15803D', tombol: 'Selesai',       mulai: (o) => o.serving_at, batas: 300 },
    };

    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    const sel = (a, b) => (a && b ? Math.max(0, (Date.parse(b) - Date.parse(a)) / 1000) : null);
    const durasi = (a, b) => { const s = sel(a, b); return s === null ? '-' : fmt(s); };
    const jam = (iso) => (iso ? new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-');
    const isiPesanan = (o) => o.items.map((i) => i.qty + 'x ' + i.name).join(', ');

    let AKTIF = [];
    let RIWAYAT = [];
    let OFFSET = 0;
    let lastSig = '';
    let lastIds = null;
    let infoTimer;

    /* ---------- kolom papan ---------- */
    $('board').innerHTML = Object.keys(TAHAP).map((k) => {
        const t = TAHAP[k];
        return '<section class="col" style="--c:' + t.warna + '" aria-label="' + t.label + '">'
            + '<h2>' + t.label + ' (<span id="n-' + k + '">0</span>)</h2><div id="col-' + k + '"></div></section>';
    }).join('');

    /* ---------- kartu pesanan ---------- */
    function jejak(o) {
        const p = [];
        if (o.taken_at) p.push('Antrian ' + durasi(o.created_at, o.taken_at));
        if (o.cooking_at) p.push('Diambil ' + durasi(o.taken_at, o.cooking_at));
        if (o.serving_at) p.push('Dimasak ' + durasi(o.cooking_at, o.serving_at));
        return p.join(' · ');
    }

    function kartu(o) {
        const t = TAHAP[o.kitchen_status];
        const sumber = o.source === 'customer' ? 'Customer (QR)' : 'Kasir';
        const belumBayar = o.status === 'menunggu_bayar' ? ' · belum dibayar' : '';
        return '<article class="card" style="--c:' + t.warna + '">'
            + '<div class="baris"><b>#' + o.id + ' · ' + esc(o.table) + '</b>'
            + '<span class="timer" data-mulai="' + esc(t.mulai(o)) + '" data-batas="' + t.batas + '">00:00</span></div>'
            + '<div class="sumber">' + sumber + belumBayar + '</div>'
            + '<div class="isi">' + esc(isiPesanan(o)) + '</div>'
            + '<div class="jejak">' + esc(jejak(o)) + '</div>'
            + '<div class="aksi">'
            + '<button class="maju" data-maju="' + o.id + '" data-from="' + o.kitchen_status + '">' + t.tombol + '</button>'
            + '<button class="cetak" data-cetak="' + o.id + '" aria-label="Cetak pesanan nomor ' + o.id + '">Cetak</button>'
            + '</div></article>';
    }

    function renderBoard() {
        Object.keys(TAHAP).forEach((k) => {
            const list = AKTIF.filter((o) => o.kitchen_status === k);
            $('n-' + k).textContent = list.length;
            $('col-' + k).innerHTML = list.length ? list.map(kartu).join('') : '<p class="kosong">Tidak ada pesanan</p>';
        });
    }

    /* ---------- riwayat ---------- */
    function renderRiwayat() {
        $('n-selesai').textContent = RIWAYAT.length;
        $('riwayat-kosong').hidden = RIWAYAT.length > 0;
        $('riwayat-body').innerHTML = RIWAYAT.map((o) =>
            '<tr><td><b>#' + o.id + '</b> · ' + esc(o.table) + '</td><td>' + esc(isiPesanan(o)) + '</td>'
            + '<td class="mono">' + durasi(o.created_at, o.taken_at) + '</td>'
            + '<td class="mono">' + durasi(o.taken_at, o.cooking_at) + '</td>'
            + '<td class="mono">' + durasi(o.cooking_at, o.serving_at) + '</td>'
            + '<td class="mono">' + durasi(o.serving_at, o.done_at) + '</td>'
            + '<td class="mono total">' + durasi(o.created_at, o.done_at) + '</td>'
            + '<td class="mono">' + jam(o.done_at) + '</td></tr>'
        ).join('');

        const total = RIWAYAT.map((o) => sel(o.created_at, o.done_at)).filter((x) => x !== null);
        $('avg').textContent = total.length
            ? 'Rata-rata waktu per pesanan: ' + fmt(total.reduce((a, b) => a + b, 0) / total.length) + ' menit (' + total.length + ' pesanan)'
            : '';
    }

    /* ---------- timer berjalan ---------- */
    function tick() {
        const now = Date.now() + OFFSET;
        document.querySelectorAll('.timer').forEach((el) => {
            const s = Math.max(0, (now - Date.parse(el.dataset.mulai)) / 1000);
            const late = s > Number(el.dataset.batas);
            el.textContent = fmt(s);
            el.classList.toggle('late', late);
            el.closest('.card').classList.toggle('late', late);
        });
    }
    setInterval(tick, 1000);

    /* ---------- notifikasi dan bunyi ---------- */
    function info(teks, err) {
        const el = $('info');
        el.textContent = teks;
        el.className = err ? 'err' : '';
        el.hidden = false;
        clearTimeout(infoTimer);
        infoTimer = setTimeout(() => { el.hidden = true; }, 4000);
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
            o.stop(c.currentTime + 0.25);
        } catch (e) { /* suara tidak tersedia, abaikan */ }
    }

    /* ---------- ambil data dari server ---------- */
    async function muat() {
        try {
            const r = await fetch(URL_DATA + '?hari=' + $('rentang').value, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (r.status === 401 || r.status === 419) { location.reload(); return; }
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();

            OFFSET = Date.parse(d.server_time) - Date.now();   // samakan jam browser dengan jam server
            AKTIF = d.aktif;
            RIWAYAT = d.riwayat;

            const ids = AKTIF.map((o) => o.id);
            if (lastIds !== null) {
                const baru = AKTIF.filter((o) => !lastIds.includes(o.id));
                if (baru.length) {
                    bip();
                    info('Pesanan baru: ' + baru.map((o) => '#' + o.id + ' ' + o.table).join(', '));
                }
            }
            lastIds = ids;

            const sig = JSON.stringify(AKTIF.map((o) => [o.id, o.kitchen_status]));
            if (sig !== lastSig) { renderBoard(); lastSig = sig; }   // gambar ulang hanya kalau ada perubahan
            renderRiwayat();
            tick();

            $('status-data').textContent = '● Real-time · ' + new Date().toLocaleTimeString('id-ID');
            $('status-data').className = 'pill live';
        } catch (e) {
            $('status-data').textContent = 'Gagal memuat, mencoba lagi…';
            $('status-data').className = 'pill err';
        }
    }

    async function maju(id, from, tombol) {
        tombol.disabled = true;
        try {
            const r = await fetch(URL_MAJU + '/' + id + '/maju', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ from: from }),
            });
            if (!r.ok) {
                const e = await r.json().catch(() => ({}));
                info(e.message || 'Gagal memperbarui pesanan.', true);
            }
        } catch (e) {
            info('Koneksi bermasalah, coba lagi.', true);
        }
        await muat();
    }

    /* ---------- cetak pesanan dapur (dirakit lewat DOM, tanpa menulis dokumen HTML) ---------- */
    function cetak(id) {
        const o = AKTIF.find((x) => x.id === id) || RIWAYAT.find((x) => x.id === id);
        if (!o) return;
        const w = window.open('', '_blank', 'width=360,height=600');
        if (!w) { info('Pop-up diblokir. Izinkan pop-up untuk situs ini lalu coba lagi.', true); return; }

        const d = w.document;
        d.title = 'Pesanan #' + o.id;

        const gaya = d.createElement('style');
        gaya.textContent = 'body{font:14px monospace;width:280px;margin:8px}h2{margin:0}hr{border:0;border-top:1px dashed #000}.q{font-size:18px;font-weight:bold}';
        d.head.appendChild(gaya);

        const kotak = d.createElement('div');
        const tambah = (tag, teks, kelas) => {
            const e = d.createElement(tag);
            e.textContent = teks;
            if (kelas) e.className = kelas;
            kotak.appendChild(e);
        };
        tambah('h2', 'DAPUR · ' + OUTLET);
        tambah('div', '#' + o.id + ' · ' + o.table);
        tambah('div', new Date(o.created_at).toLocaleString('id-ID'));
        kotak.appendChild(d.createElement('hr'));
        o.items.forEach((i) => tambah('div', i.qty + 'x ' + i.name, 'q'));
        kotak.appendChild(d.createElement('hr'));
        tambah('div', o.source === 'customer' ? 'Customer (QR)' : 'Kasir');
        d.body.appendChild(kotak);

        setTimeout(() => { w.focus(); w.print(); }, 250);
    }

    $('board').addEventListener('click', (e) => {
        const m = e.target.closest('[data-maju]');
        if (m) { maju(Number(m.dataset.maju), m.dataset.from, m); return; }
        const c = e.target.closest('[data-cetak]');
        if (c) cetak(Number(c.dataset.cetak));
    });
    $('rentang').addEventListener('change', muat);

    muat();
    setInterval(muat, 3000);
})();
