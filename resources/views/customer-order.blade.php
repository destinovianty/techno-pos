<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesan · {{ $outletName }} · Meja {{ $table }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#F7F1E8; --ink:#231F1C; --mut:#6B5F55; --line:#E7DDD0; --chip:#EFE6DA; --err:#B91C1C; --ok:#15803D; --warn:#B45309; --a:{{ $color }}; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, sans-serif; background:var(--bg); color:var(--ink); line-height:1.5; -webkit-tap-highlight-color: transparent; }
        [hidden] { display:none !important; }
        .sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
        .app { max-width:480px; margin:0 auto; min-height:100vh; background:var(--bg); }

        .hd { background:#231F1C; color:#fff; border-bottom:4px solid var(--a); padding:calc(16px + env(safe-area-inset-top)) 16px 14px; }
        .hd h1 { margin:0; font-size:18px; font-weight:800; line-height:1.3; }
        .hd p { margin:2px 0 0; font-size:13px; color:#D6CCC0; }
        .meja { display:inline-block; background:var(--a); color:#fff; font-size:13px; font-weight:700; padding:2px 10px; border-radius:20px; margin-left:8px; vertical-align:2px; }

        .view { padding:16px 16px 150px; }
        .view h2 { margin:0 0 12px; font-size:18px; font-weight:800; }
        .view h2:focus { outline:none; }
        .muted { font-size:13px; color:var(--mut); margin:0; }
        .row { display:flex; justify-content:space-between; align-items:center; gap:8px; }

        .cari { width:100%; height:48px; font:inherit; font-size:16px; padding:0 16px; border:2px solid var(--a); border-radius:12px; background:#fff; margin-bottom:12px; }
        .grup { display:flex; gap:4px; background:var(--chip); padding:4px; border-radius:14px; margin-bottom:10px; }
        .grup button { flex:1; min-width:0; min-height:42px; border:0; border-radius:10px; background:transparent; font:inherit; font-weight:700; color:var(--mut); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; }
        .grup button[aria-pressed=true] { background:#fff; color:var(--a); font-weight:800; box-shadow:0 1px 4px rgba(35,31,28,.18); }
        .cats { display:flex; gap:8px; overflow-x:auto; padding:2px 2px 8px; margin-bottom:8px; scrollbar-width:none; }
        .cats::-webkit-scrollbar { display:none; }
        .cats button { flex:none; min-height:42px; padding:0 14px; border:1.5px solid var(--line); border-radius:999px; background:#fff; color:var(--ink); font:inherit; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap; }
        .cats button[aria-pressed=true] { background:var(--a); border-color:var(--a); color:#fff; }
        .ct { font-size:12px; font-weight:800; background:var(--chip); color:var(--mut); padding:1px 8px; border-radius:999px; }
        [aria-pressed=true] .ct { background:rgba(255,255,255,.28); color:#fff; }
        .grup [aria-pressed=true] .ct { background:var(--chip); color:var(--mut); }

        .item { display:flex; gap:12px; align-items:center; background:#fff; border:1px solid var(--line); border-radius:16px; padding:12px; margin-bottom:10px; box-shadow:0 2px 6px rgba(35,31,28,.06); }
        .item.off { opacity:.55; }
        .tile { width:56px; height:56px; border-radius:12px; background:var(--a); opacity:.18; flex:none; }
        .info { flex:1; min-width:0; }
        .nm { font-weight:700; }
        .hg { font-weight:800; color:var(--a); }
        .sk { font-size:12px; color:var(--mut); }
        .sk.low { color:var(--warn); font-weight:700; }
        .addbtn { min-height:44px; padding:0 16px; border:0; border-radius:12px; background:var(--a); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        .addbtn:disabled { background:#B9AEA3; cursor:not-allowed; }
        .stp { display:flex; align-items:center; gap:4px; }
        .stp button { width:44px; height:44px; border:1px solid var(--line); border-radius:12px; background:#fff; font:inherit; font-size:20px; font-weight:800; cursor:pointer; }
        .stp b { min-width:24px; text-align:center; }
        .lebih { width:100%; min-height:48px; border:1.5px solid var(--a); border-radius:12px; background:#fff; color:var(--a); font:inherit; font-weight:800; cursor:pointer; margin:4px 0 12px; }

        .bar { position:fixed; left:0; right:0; bottom:0; max-width:480px; margin:0 auto; background:#fff; border-top:1px solid var(--line); padding:12px 16px calc(12px + env(safe-area-inset-bottom)); display:flex; flex-direction:column; gap:10px; z-index:20; }
        .btn { width:100%; min-height:52px; border:0; border-radius:14px; background:var(--a); color:#fff; font:inherit; font-size:16px; font-weight:800; cursor:pointer; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .btn.sec { background:var(--chip); color:var(--ink); font-size:15px; }
        .link { background:none; border:0; font:inherit; font-weight:700; color:var(--a); cursor:pointer; padding:8px 0; min-height:44px; }

        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:16px; margin-bottom:12px; box-shadow:0 2px 6px rgba(35,31,28,.06); }
        .ringkas { display:flex; flex-direction:column; gap:6px; font-size:14px; }
        .total { font-size:22px; font-weight:800; color:var(--a); }
        fieldset { border:0; margin:16px 0 0; padding:0; }
        legend { font-weight:800; margin-bottom:8px; padding:0; }
        .opt { display:flex; gap:12px; align-items:flex-start; background:#fff; border:2px solid var(--line); border-radius:14px; padding:14px; margin-bottom:10px; cursor:pointer; }
        .opt:has(input:checked) { border-color:var(--a); background:#FFF9F4; }
        .opt input { width:22px; height:22px; margin:2px 0 0; accent-color:var(--a); flex:none; }
        .opt b { display:block; }
        #pesan { font-size:14px; font-weight:600; margin:8px 0 0; min-height:22px; }
        #pesan.err { color:var(--err); } #pesan.ok { color:var(--ok); }

        .notice { background:#FFF7ED; border:1px solid #FDBA74; border-radius:12px; padding:12px; font-size:14px; margin-bottom:12px; }
        .notice.demo { background:#FEF3C7; border-color:#F59E0B; }
        .qrbox { display:flex; justify-content:center; padding:16px 0; }
        #qr { background:#fff; padding:12px; border-radius:12px; border:1px solid var(--line); min-width:244px; min-height:244px; display:flex; align-items:center; justify-content:center; text-align:center; font-size:13px; color:var(--mut); }
        #qr img, #qr canvas { max-width:100%; height:auto; }

        .badge { display:inline-block; font-size:12px; font-weight:700; padding:2px 10px; border-radius:20px; }
        .badge.wait { background:#FEF3C7; color:var(--warn); }
        .badge.ok { background:#DCFCE7; color:var(--ok); }
        .badge.bad { background:#FEE2E2; color:var(--err); }
        .steps { list-style:none; margin:12px 0; padding:0; }
        .steps li { display:flex; align-items:center; gap:12px; padding:6px 0; color:var(--mut); }
        .steps li::before { content:''; width:14px; height:14px; border-radius:50%; border:2px solid var(--line); background:#fff; flex:none; }
        .steps li.done { color:var(--ink); }
        .steps li.done::before { background:var(--ok); border-color:var(--ok); }
        .steps li.now { color:var(--ink); font-weight:800; }
        .steps li.now::before { background:var(--a); border-color:var(--a); box-shadow:0 0 0 4px rgba(0,0,0,.08); }
        .isi { font-size:13px; color:var(--mut); border-top:1px solid var(--line); padding-top:10px; }
        .live { font-size:12px; color:var(--ok); margin:0 0 12px; }
        .live.err { color:var(--err); }

        button:focus-visible, input:focus-visible { outline:3px solid #231F1C; outline-offset:2px; }
    </style>
</head>
<body>
<div class="app">
    <header class="hd">
        <h1>{{ $outletName }}<span class="meja">Meja {{ $table }}</span></h1>
        <p>{{ $label }} · Pesan langsung dari meja</p>
    </header>

    {{-- ========== MENU ========== --}}
    <section class="view" id="v-menu" aria-labelledby="h-menu">
        <h2 id="h-menu" class="sr" tabindex="-1">Menu</h2>
        <div class="notice" id="aktif-info" hidden>
            <div class="row"><span id="aktif-teks">Ada pesanan yang sedang diproses.</span>
            <button class="link" id="lihat-status" type="button">Lihat status</button></div>
        </div>
        <input type="search" id="cari" class="cari" autocomplete="off" placeholder="Cari menu…" aria-label="Cari menu">
        <div class="grup" id="grup" role="group" aria-label="Jenis menu" hidden></div>
        <div class="cats" id="cats" role="group" aria-label="Kategori"></div>
        <div id="menu"></div>
        <div id="lebih-wrap"></div>
        <p class="muted" id="kosong" hidden>Tidak ada menu yang cocok.</p>
        <div class="bar" id="bar" hidden>
            <div class="row"><span id="bar-info"></span><b id="bar-total"></b></div>
            <button class="btn" id="ke-cart" type="button">Lihat pesanan</button>
        </div>
    </section>

    {{-- ========== KERANJANG ========== --}}
    <section class="view" id="v-cart" aria-labelledby="h-cart" hidden>
        <button class="link" id="kembali-menu" type="button">← Kembali ke menu</button>
        <h2 id="h-cart" tabindex="-1">Pesanan Anda</h2>
        <div id="cart-list"></div>
        <div class="card ringkas">
            <div class="row"><span class="muted">Subtotal</span><span id="c-sub">Rp 0</span></div>
            <div class="row" id="c-service-row" hidden><span class="muted" id="c-service-label">Service charge</span><span id="c-service">Rp 0</span></div>
            <div class="row"><b>Total</b><span class="total" id="cart-total">Rp 0</span></div>
        </div>

        <fieldset>
            <legend>Cara bayar</legend>
            <label class="opt"><input type="radio" name="pay" value="qris" checked>
                <span><b>Bayar sekarang dengan QRIS</b><span class="muted">Scan dari m-banking atau e-wallet.</span></span></label>
            <label class="opt"><input type="radio" name="pay" value="kasir">
                <span><b>Bayar di kasir</b><span class="muted">Pesanan langsung diproses, bayar saat selesai.</span></span></label>
        </fieldset>
        <p id="pesan" role="status" aria-live="polite"></p>

        <div class="bar">
            <button class="btn" id="kirim" type="button">Kirim Pesanan</button>
        </div>
    </section>

    {{-- ========== QRIS ========== --}}
    <section class="view" id="v-qris" aria-labelledby="h-qris" hidden>
        <h2 id="h-qris" tabindex="-1">Bayar dengan QRIS</h2>
        @if ($demo)
            <div class="notice demo"><b>Mode simulasi.</b> Kode QR di bawah hanya contoh dan belum tersambung ke payment gateway. Tombol "Saya sudah bayar" langsung menandai pesanan lunas.</div>
        @endif
        <div class="card" style="text-align:center">
            <div class="muted">Total pembayaran</div>
            <div class="total" id="qris-total">Rp 0</div>
            <div class="muted" id="qris-service"></div>
            <div class="qrbox"><div id="qr" role="img" aria-label="Kode QRIS"></div></div>
            <div class="muted">Kode pesanan <b id="qris-kode"></b></div>
        </div>
        <div class="bar">
            @if ($demo)
                <button class="btn" id="sudah-bayar" type="button">Saya sudah bayar</button>
            @endif
            <button class="btn sec" id="qris-status" type="button">Lihat status pesanan</button>
        </div>
    </section>

    {{-- ========== STATUS ========== --}}
    <section class="view" id="v-status" aria-labelledby="h-status" hidden>
        <h2 id="h-status" tabindex="-1">Status Pesanan</h2>
        <p class="live" id="live" role="status">Menghubungkan…</p>
        <div id="orders"></div>
        <div class="bar">
            <button class="btn" id="pesan-lagi" type="button">Pesan lagi</button>
        </div>
    </section>
</div>

<script>
    window.CUSTOMER = {
        slug: @json($slug),
        outlet: @json($outletName),
        table: @json($table),
        service: @json($service),
        products: @json($produkJs),
        urlStore: @json(route('customer.store', ['outlet' => $slug, 'table' => $table])),
        urlStatus: @json(url('/order/status')),
        urlOrder: @json(url('/order')),
        demo: @json($demo)
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="{{ asset('js/customer.js') }}"></script>
</body>
</html>
