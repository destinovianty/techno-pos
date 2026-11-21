<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir · {{ $outletName }} · Techno POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#F7F1E8; --ink:#231F1C; --mut:#6B5F55; --line:#E7DDD0; --chip:#EFE6DA; --err:#B91C1C; --ok:#15803D; --a:{{ $color }}; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, sans-serif; background:var(--bg); color:var(--ink); line-height:1.5; }
        [hidden] { display:none !important; }
        .sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }

        /* Header */
        header { background:#231F1C; color:#fff; border-bottom:4px solid var(--a); }
        .top { display:flex; align-items:center; gap:8px 12px; min-height:64px; padding:10px 24px; flex-wrap:wrap; }
        .brand { font-size:20px; font-weight:800; }
        .chip { font-size:13px; font-weight:700; padding:6px 12px; border-radius:20px; background:#3A332E; white-space:nowrap; }
        .chip.a { background:var(--a); }
        .live { margin-left:auto; font-size:13px; color:#8FD9A8; }
        .live.err { color:#FCA5A5; }
        .aksi-top { display:flex; align-items:center; gap:8px; }
        .aksi-top form { margin:0; }
        .out { display:inline-block; font:inherit; font-size:14px; font-weight:700; padding:8px 16px; border-radius:10px; border:0; background:#EFE6DA; color:var(--ink); cursor:pointer; text-decoration:none; white-space:nowrap; }

        /* Lebar kolom kiri dikunci (minmax(0,1fr)) supaya isinya tidak bisa melebarkan halaman */
        .layout { display:grid; grid-template-columns: minmax(0, 1fr) 400px; gap:20px; padding:20px 24px 48px; max-width:1400px; margin:0 auto; align-items:start; }
        .layout > section, .layout > aside { min-width:0; }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:16px; box-shadow:0 2px 6px rgba(35,31,28,.07); }
        h2 { margin:0 0 12px; font-size:16px; font-weight:800; }
        .lh { margin:0 0 8px; font-size:14px; font-weight:800; }

        /* Pencarian dan kategori */
        .tools { background:#fff; display:flex; flex-direction:column; gap:12px; padding:4px 0 14px; margin-bottom:14px; border-bottom:1px solid var(--line); }
        .carirow { display:flex; gap:10px; }
        .cari { flex:1; min-width:0; height:48px; font:inherit; padding:0 16px; border:2px solid var(--a); border-radius:12px; background:#fff; }
        .tambahmenu { flex:none; height:48px; padding:0 18px; border:2px solid var(--a); border-radius:12px; background:#fff; color:var(--a); font:inherit; font-weight:800; cursor:pointer; white-space:nowrap; }
        .tambahmenu:hover, .tambahmenu[aria-pressed=true] { background:var(--a); color:#fff; }
        .info-menu { margin:0; font-size:13px; font-weight:600; color:var(--ok); }
        .info-katalog { margin:0; font-size:13px; color:var(--mut); background:#FFF7ED; border:1px solid #FDBA74; border-radius:10px; padding:8px 12px; }
        .grup { display:flex; gap:4px; background:var(--chip); padding:4px; border-radius:14px; }
        .grup button { flex:1; min-width:0; min-height:40px; padding:0 8px; border:0; border-radius:10px; background:transparent; font:inherit; font-weight:700; color:var(--mut); cursor:pointer; display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:2px 8px; white-space:nowrap; }
        .grup button:hover { color:var(--ink); }
        .grup button[aria-pressed=true] { background:#fff; color:var(--a); font-weight:800; box-shadow:0 1px 4px rgba(35,31,28,.18); }
        .cats { display:flex; flex-wrap:wrap; gap:8px; }
        .cats button { min-height:40px; padding:0 14px; border:1.5px solid var(--line); border-radius:999px; background:#fff; color:var(--ink); font:inherit; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap; }
        .cats button:hover { border-color:var(--a); }
        .cats button[aria-pressed=true] { background:var(--a); border-color:var(--a); color:#fff; box-shadow:0 2px 8px rgba(0,0,0,.18); }
        .ct { font-size:12px; font-weight:800; background:var(--chip); color:var(--mut); padding:1px 8px; border-radius:999px; }
        .cats button[aria-pressed=true] .ct { background:rgba(255,255,255,.28); color:#fff; }

        /* Pilihan kategori berbentuk dropdown (dipakai di HP kalau kategorinya banyak) */
        .kat-select { display:none; }
        .kat-select select { width:100%; height:46px; font:inherit; font-size:16px; font-weight:700; padding:0 12px; border:1.5px solid var(--line); border-radius:12px; background:#fff; color:var(--ink); }

        .laris { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:10px; margin-bottom:16px; }
        .lr { min-width:0; text-align:left; background:#fff; border:2px solid var(--a); border-radius:14px; padding:10px 12px; font:inherit; cursor:pointer; display:flex; flex-direction:column; gap:2px; }
        .lr:disabled { opacity:.5; cursor:not-allowed; border-color:var(--line); }
        .lr b { font-size:14px; overflow-wrap:anywhere; }
        .lr .hrg2 { font-weight:800; color:var(--a); font-size:14px; }
        .lr .stok { font-size:12px; color:var(--mut); }
        .lbadge { align-self:flex-start; display:inline-block; font-size:11px; font-weight:800; background:var(--a); color:#fff; padding:1px 8px; border-radius:10px; margin-bottom:4px; }
        .lbadge.ubah { background:#231F1C; }

        .menu { display:grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap:12px; }
        .prod { min-width:0; text-align:left; background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px; font:inherit; cursor:pointer; box-shadow:0 2px 6px rgba(35,31,28,.07); }
        .prod:hover:not(:disabled) { border-color:var(--a); }
        .prod:disabled { opacity:.5; cursor:not-allowed; }
        .prod .tile { height:48px; border-radius:10px; background:var(--a); opacity:.18; margin-bottom:10px; }
        .prod b { display:block; overflow-wrap:anywhere; }
        .prod .hrg { font-weight:800; color:var(--a); margin:6px 0 2px; }
        .prod .stok { font-size:12px; color:var(--mut); }
        .prod .stok.low { color:#B45309; font-weight:700; }
        .menu.mode-kelola .prod { border:2px dashed var(--a); }

        /* Paginasi */
        .halaman { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:16px; padding-top:14px; border-top:1px solid var(--line); }
        .hal-info { font-size:13px; color:var(--mut); }
        .hal-nav { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .hal-nav button { min-width:44px; min-height:44px; padding:0 12px; border:1.5px solid var(--line); border-radius:10px; background:#fff; color:var(--ink); font:inherit; font-weight:700; cursor:pointer; }
        .hal-nav button:hover:not(:disabled):not([aria-current]) { border-color:var(--a); color:var(--a); }
        .hal-nav button[aria-current=page] { background:var(--a); border-color:var(--a); color:#fff; }
        .hal-nav button:disabled { opacity:.45; cursor:not-allowed; }
        .hal-nav .titik { padding:0 4px; color:var(--mut); }

        .row { display:flex; justify-content:space-between; align-items:center; gap:8px; }
        .line { padding:8px 0; border-bottom:1px solid var(--line); }
        .tag { display:inline-block; font-size:11px; font-weight:700; background:var(--chip); color:var(--mut); padding:0 8px; border-radius:999px; margin-left:6px; vertical-align:1px; }
        .tag.warn { background:#FEF3C7; color:#B45309; }
        .qty { display:flex; align-items:center; gap:6px; }
        .qty button { width:32px; height:32px; border-radius:8px; border:1px solid var(--line); background:#fff; font:inherit; font-weight:800; cursor:pointer; }
        .muted { font-size:13px; color:var(--mut); margin:0; }
        .total { font-size:22px; font-weight:800; color:var(--a); }
        label { display:block; font-size:13px; font-weight:700; margin:12px 0 4px; }
        select, input[type=number], input[type=text] { width:100%; height:44px; font:inherit; padding:0 12px; border:1px solid #D8CCBD; border-radius:10px; background:#fff; }
        .seg { display:flex; gap:8px; }
        .seg button { flex:1; min-height:44px; border:0; border-radius:10px; background:var(--chip); font:inherit; font-weight:700; cursor:pointer; }
        .seg button[aria-pressed=true] { background:var(--a); color:#fff; }
        .btn { width:100%; min-height:48px; border:0; border-radius:12px; font:inherit; font-weight:800; font-size:16px; background:var(--a); color:#fff; cursor:pointer; margin-top:16px; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .btn.sec { background:var(--chip); color:var(--ink); font-size:14px; margin-top:8px; }
        .btn.danger { background:#fff; color:var(--err); border:2px solid var(--err); }
        .small { min-height:36px; padding:0 12px; border:0; border-radius:8px; background:var(--a); color:#fff; font:inherit; font-size:13px; font-weight:700; cursor:pointer; }
        .small.sec { background:var(--chip); color:var(--ink); }
        .pend { border:1px solid var(--line); border-radius:12px; padding:12px; margin-bottom:12px; }
        #pesan { font-size:14px; margin:12px 0 0; min-height:22px; font-weight:600; }
        #pesan.err { color:var(--err); } #pesan.ok { color:var(--ok); }

        /* Parkir, ringkasan, dan diskon di keranjang */
        .parkir { margin-top:12px; padding:10px 12px; border:1px dashed var(--line); border-radius:12px; }
        .parkir .lh { margin:0 0 8px; }
        .pk-wrap { display:flex; gap:8px; }
        .pk { flex:1; min-height:44px; border:1.5px solid var(--a); border-radius:10px; background:#fff; color:var(--a); font:inherit; font-weight:800; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.2; padding:4px 8px; }
        .pk span { font-size:12px; font-weight:700; }
        .pk:hover { background:var(--a); color:#fff; }
        .ringkas { margin-top:12px; display:flex; flex-direction:column; gap:6px; font-size:14px; }
        .ringkas .row b.neg { color:var(--ok); }
        .diskon { margin-top:12px; padding:10px 12px; background:var(--bg); border-radius:12px; }
        .diskon .lh { margin:0 0 8px; }
        .diskon-row { display:flex; gap:8px; }
        .diskon-row select { width:84px; flex:none; }
        .diskon-row input { flex:1; min-width:0; }
        .diskon input[type=text] { margin-top:8px; }
        .d-pesan { font-size:13px; font-weight:600; color:var(--err); margin:6px 0 0; min-height:0; }
        button:focus-visible, select:focus-visible, input:focus-visible, a:focus-visible { outline:3px solid #231F1C; outline-offset:2px; }

        /* Notifikasi pesanan baru dari customer */
        #toast { position:fixed; left:50%; bottom:24px; transform:translateX(-50%); background:#231F1C; color:#fff; padding:12px 20px; border-radius:20px; font-size:14px; font-weight:700; z-index:50; max-width:calc(100vw - 32px); box-shadow:0 8px 24px rgba(0,0,0,.3); border:2px solid var(--a); }

        /* Bar bawah di HP: jumlah item, total, dan tombol ke keranjang */
        .mbar { display:none; }

        /* Jendela tambah / ubah menu */
        dialog { border:0; border-radius:16px; padding:0; width:min(520px, calc(100vw - 32px)); max-height:calc(100vh - 32px); overflow:auto; color:var(--ink); box-shadow:0 20px 50px rgba(0,0,0,.35); }
        dialog::backdrop { background:rgba(35,31,28,.55); }
        .dlg-head { padding:18px 20px 0; }
        .dlg-head h2 { margin:0; font-size:18px; }
        .dlg-body { padding:4px 20px 8px; }
        .dua { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .cek { display:flex; align-items:center; gap:10px; font-weight:600; margin:14px 0 0; cursor:pointer; }
        .cek input { width:20px; height:20px; accent-color:var(--a); flex:none; }
        .m-pesan { font-size:14px; font-weight:600; color:var(--err); margin:12px 0 0; min-height:22px; }
        .dlg-foot { display:flex; flex-wrap:wrap; gap:10px; padding:8px 20px 20px; }
        .dlg-foot .btn { margin:0; flex:1; width:auto; font-size:15px; min-width:110px; }

        /* Pencarian dan kategori menempel di atas hanya pada layar yang cukup tinggi */
        @media (min-width: 1024px) and (min-height: 900px) {
            .tools { position:sticky; top:0; z-index:5; }
        }

        /* Tablet dan HP: satu kolom, ada bar bawah */
        @media (max-width: 1023px) {
            .layout { grid-template-columns: minmax(0, 1fr); padding-bottom:110px; }
            #toast { bottom:92px; }
            .mbar { position:fixed; left:0; right:0; bottom:0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:12px; background:#fff; border-top:3px solid var(--a); padding:10px 14px calc(10px + env(safe-area-inset-bottom)); box-shadow:0 -4px 16px rgba(35,31,28,.12); }
            .mbar-info { font-size:12px; color:var(--mut); line-height:1.2; }
            .mbar b { font-size:18px; color:var(--a); }
            .mbar-btn { min-height:46px; padding:0 20px; border:0; border-radius:12px; background:var(--a); color:#fff; font:inherit; font-weight:800; cursor:pointer; }
        }

        /* HP */
        @media (max-width: 767px) {
            .top { padding:10px 14px; gap:8px 10px; min-height:0; }
            .brand { font-size:18px; }
            .chip { font-size:12px; padding:5px 10px; }
            .chip.lbl { display:none; }
            .aksi-top { margin-left:auto; }
            .live { order:9; flex-basis:100%; margin-left:0; font-size:12px; }
            .out { padding:7px 12px; font-size:13px; }
            .layout { padding:12px 12px 110px; gap:14px; }
            .card { padding:12px; border-radius:14px; }
            .carirow { gap:8px; }
            .cari { height:46px; font-size:16px; padding:0 12px; }
            .tambahmenu { height:46px; padding:0 12px; }
            .lg { display:none; }
            .hp-sembunyi { display:none; }
            .grup button { font-size:14px; padding:0 4px; }
            .menu, .laris { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
            .prod { padding:10px; }
            .prod .tile { height:36px; margin-bottom:8px; }
            .prod b { font-size:14px; }
            .cats.banyak { display:none; }
            .kat-select.banyak { display:block; }
            .halaman { justify-content:center; }
            .hal-info { width:100%; text-align:center; }
            .hal-nav { width:100%; justify-content:center; }
            .hal-nav .tx { display:none; }
        }
        @media (max-width: 520px) {
            .dua { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="top">
            <span class="brand">Techno POS</span>
            <span class="chip a">{{ $outletName }}</span>
            <span class="chip lbl">{{ $label }}</span>
            <span class="chip">Kasir · {{ $userName }}</span>
            <span class="live" id="status" role="status">Memuat…</span>
            <div class="aksi-top">
                @if ($tables > 0)
                    <a class="out" href="{{ route('kasir.qr') }}" target="_blank" rel="noopener">QR Meja</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="out">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <div class="layout">
        <section>
            <div class="card">
                <h2>Menu · {{ $outletName }}</h2>
                <div class="tools" id="tools">
                    <div class="grup" id="katalog" role="group" aria-label="Pilih menu outlet" hidden></div>
                    <p class="info-katalog" id="info-katalog" hidden></p>
                    <div class="carirow">
                        <input type="search" id="cari" class="cari" autocomplete="off"
                               placeholder="{{ $barcode ? 'Scan barcode atau ketik nama produk, lalu Enter' : 'Cari menu…' }}"
                               aria-label="Cari produk">
                        <button type="button" class="tambahmenu" id="buka-menu">+ Tambah<span class="lg"> menu</span></button>
                        <button type="button" class="tambahmenu" id="kelola" aria-pressed="false">Kelola</button>
                    </div>
                    <p class="info-menu" id="info-menu" role="status" aria-live="polite" hidden></p>
                    <p class="info-katalog" id="info-kelola" hidden><b>Mode kelola menu.</b> Klik menu untuk mengubah atau menghapusnya. Tekan <b>Selesai</b> untuk kembali berjualan.</p>
                    <div class="grup" id="grup" role="group" aria-label="Jenis menu" hidden></div>
                    <div class="cats" id="cats" role="group" aria-label="Kategori" hidden></div>
                    <div class="kat-select" id="kat-wrap" hidden>
                        <label for="kat-select" class="sr">Kategori</label>
                        <select id="kat-select"></select>
                    </div>
                </div>

                <div id="box-laris" hidden>
                    <h3 class="lh">Terlaris · 30 hari terakhir</h3>
                    <div class="laris" id="laris"></div>
                </div>

                <div class="menu" id="menu"></div>
                <p class="muted" id="kosong" hidden>Tidak ada produk yang cocok.</p>
                <div class="halaman" id="halaman" hidden></div>
            </div>
            <div class="card" style="margin-top:20px" id="box-pending" hidden>
                <h2>Pesanan customer menunggu bayar (<span id="n-pending">0</span>)</h2>
                <div id="pending"></div>
            </div>
        </section>

        <aside class="card" id="keranjang" aria-label="Keranjang">
            <h2>Pesanan</h2>

            @if ($tables > 0)
                <label for="meja">{{ $type === 'billiard' ? 'Meja billiard' : 'Meja' }}</label>
                <select id="meja">
                    @for ($i = 1; $i <= $tables; $i++)
                        <option value="{{ $i }}">{{ $type === 'billiard' ? 'Meja billiard' : 'Meja' }} {{ $i }}</option>
                    @endfor
                </select>
            @endif

            <div class="parkir" id="parkir" hidden>
                <h3 class="lh">Parkir</h3>
                <div class="pk-wrap" id="parkir-btn"></div>
            </div>

            <div id="cart" style="margin-top:12px"></div>

            <div class="ringkas">
                <div class="row"><span class="muted">Subtotal</span><span id="r-sub">Rp 0</span></div>
                <div class="row" id="baris-diskon" hidden><span class="muted">Diskon</span><b class="neg" id="r-diskon">Rp 0</b></div>
                <div class="row" id="baris-service" hidden><span class="muted" id="r-service-label">Service charge</span><span id="r-service">Rp 0</span></div>
                <div class="row"><b>Total</b><span class="total" id="total">Rp 0</span></div>
            </div>

            <div class="diskon">
                <h3 class="lh">Diskon</h3>
                <div class="diskon-row">
                    <select id="d-tipe" aria-label="Jenis diskon">
                        <option value="rupiah">Rp</option>
                        <option value="persen">%</option>
                    </select>
                    <input type="number" id="d-nilai" min="0" step="1" inputmode="numeric" placeholder="0" aria-label="Nilai diskon">
                </div>
                <input type="text" id="d-note" maxlength="100" placeholder="Alasan diskon (opsional)" aria-label="Alasan diskon">
                <p class="d-pesan" id="d-pesan" role="alert"></p>
            </div>

            <label>Metode bayar</label>
            <div class="seg" id="metode">
                <button type="button" data-m="tunai" aria-pressed="true">Tunai</button>
                <button type="button" data-m="qris" aria-pressed="false">QRIS</button>
                <button type="button" data-m="debit" aria-pressed="false">Debit / Transfer</button>
            </div>

            <div id="box-tunai">
                <label for="paid">Uang diterima (Rp)</label>
                <input type="number" id="paid" min="0" step="1000" placeholder="0">
                <div class="row" style="margin-top:8px"><span class="muted">Kembalian</span><b id="kembali">Rp 0</b></div>
            </div>

            <button class="btn" id="bayar" disabled>{{ $kitchen ? 'Bayar & Kirim ke Dapur' : 'Bayar & Cetak Struk' }}</button>
            <button class="btn sec" id="kosongkan" type="button">Kosongkan keranjang</button>
            <p id="pesan" role="status" aria-live="polite"></p>
        </aside>
    </div>

    {{-- Bar bawah (hanya terlihat di tablet dan HP) --}}
    <div class="mbar" id="mbar" hidden>
        <div>
            <div class="mbar-info" id="mbar-info">0 item</div>
            <b id="mbar-total">Rp 0</b>
        </div>
        <button type="button" class="mbar-btn" id="mbar-btn">Lihat pesanan ›</button>
    </div>

    <div id="toast" role="status" aria-live="polite" hidden></div>

    {{-- ========== TAMBAH / UBAH MENU ========== --}}
    <dialog id="dlg-menu" aria-labelledby="dlg-judul">
        <form id="form-menu" novalidate>
            <div class="dlg-head"><h2 id="dlg-judul">Tambah menu baru · {{ $outletName }}</h2></div>
            <div class="dlg-body">
                <label for="m-nama">Nama menu</label>
                <input type="text" id="m-nama" maxlength="255" autocomplete="off">

                <div class="dua">
                    <div>
                        <label for="m-harga">Harga jual (Rp)</label>
                        <input type="number" id="m-harga" min="1" step="500" inputmode="numeric">
                    </div>
                    <div>
                        <label for="m-hpp" id="l-hpp">HPP (Rp), boleh kosong</label>
                        <input type="number" id="m-hpp" min="0" step="500" inputmode="numeric">
                    </div>
                </div>
                <p class="muted" id="m-hpp-note" style="margin-top:6px"></p>

                <label for="m-kat">Kategori</label>
                <select id="m-kat"></select>
                <div id="m-kat-baru-wrap" hidden>
                    <label for="m-kat-baru">Nama kategori baru</label>
                    <input type="text" id="m-kat-baru" maxlength="50" autocomplete="off">
                </div>

                <label class="cek" for="m-track"><input type="checkbox" id="m-track" checked> Pakai stok</label>
                <div id="m-stok-wrap">
                    <label for="m-stok" id="l-stok">Stok awal</label>
                    <input type="number" id="m-stok" min="0" step="1" inputmode="numeric">
                    <p class="muted" id="m-stok-note" style="margin-top:6px" hidden>Mengisi angka ini menimpa stok yang tercatat sekarang.</p>
                </div>

                <label class="cek" id="m-dapur-wrap" for="m-dapur"><input type="checkbox" id="m-dapur" checked> Masuk dapur (dimasak dulu sebelum disajikan)</label>

                <div id="m-barcode-wrap" hidden>
                    <label for="m-barcode">Barcode, boleh kosong</label>
                    <input type="text" id="m-barcode" maxlength="50" autocomplete="off" inputmode="numeric">
                </div>

                <p class="m-pesan" id="m-pesan" role="alert"></p>
            </div>
            <div class="dlg-foot">
                <button type="button" class="btn danger" id="m-hapus" hidden>Hapus menu</button>
                <button type="button" class="btn sec" id="m-batal">Batal</button>
                <button type="submit" class="btn" id="m-simpan">Simpan menu</button>
            </div>
        </form>
    </dialog>

    <script>
        window.KASIR = {
            urlData: @json(route('kasir.data')),
            urlStore: @json(route('kasir.store')),
            urlOrder: @json(url('/kasir/pesanan')),
            urlMenu: @json(route('kasir.menu')),
            urlMenuBase: @json(url('/kasir/menu')),
            outlet: @json($outletName),
            tables: @json($tables),
            kitchen: @json($kitchen),
            barcode: @json($barcode),
            service: @json($service),
            diskonMaks: @json($diskonMaks),
            linked: @json($linked),
            products: @json($produkJs)
        };
    </script>
    <script src="{{ asset('js/cetak.js') }}"></script>
    <script src="{{ asset('js/kasir.js') }}"></script>
    <script src="{{ asset('js/kasir.js') }}"></script>
</body>
</html>
