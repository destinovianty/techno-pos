<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dapur · {{ $outletName }} · Techno POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#F7F1E8; --ink:#231F1C; --mut:#6B5F55; --line:#E7DDD0; --late:#B91C1C; --accent:#D9480F; --oc:{{ $color }}; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; }
        [hidden] { display:none !important; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

        header { background:#fff; border-bottom:1px solid var(--line); }
        .top { display:flex; align-items:center; gap:16px; min-height:64px; flex-wrap:wrap; }
        .brand { font-size:22px; font-weight:700; }
        .brand span { color: var(--accent); }
        .chip { font-size:13px; font-weight:600; padding:6px 12px; border-radius:20px; background:#F3ECE2; }
        .chip.outlet { background: var(--oc); color:#fff; }
        .spacer { margin-left:auto; }
        .btn-out { font:inherit; font-size:14px; font-weight:600; padding:8px 16px; border-radius:10px; border:1px solid var(--line); background:#fff; cursor:pointer; }

        .banner { margin: 16px 0; border-radius:16px; background: var(--oc); color:#fff; padding:16px 24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .banner h1 { margin:0; font-size:22px; line-height:1.2; }
        .banner small { opacity:.9; }
        .pill { font-size:13px; font-weight:600; background:#fff; color:var(--ink); padding:6px 12px; border-radius:20px; }
        .pill.live { color:#15803D; }
        .pill.err { color:var(--late); }

        .board { display:grid; grid-template-columns: repeat(4, 1fr); gap:24px; align-items:start; }
        .col h2 { margin:0 0 12px; font-size:17px; color: var(--c); }
        .card { background:#fff; border:1px solid var(--line); border-left:4px solid var(--c); border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 4px 12px rgba(0,0,0,.06); }
        .card.late { border-color: var(--late); border-left-color: var(--late); }
        .baris { display:flex; justify-content:space-between; align-items:center; gap:8px; }
        .timer { font-family: ui-monospace, Consolas, monospace; font-weight:700; font-size:18px; }
        .timer.late { color: var(--late); }
        .sumber { font-size:12px; color: var(--mut); }
        .isi { margin:12px 0; font-weight:600; }
        .jejak { font-size:12px; color: var(--mut); min-height:18px; margin-bottom:12px; }
        .aksi { display:flex; gap:8px; }
        .maju { flex:1; min-height:44px; border:0; border-radius:10px; color:#fff; background: var(--c); font:inherit; font-weight:700; cursor:pointer; }
        .maju:disabled { opacity:.6; cursor:wait; }
        .cetak { min-height:44px; padding:0 14px; border:1px solid var(--line); background:#fff; border-radius:10px; font:inherit; font-size:13px; font-weight:600; cursor:pointer; }
        .kosong { font-size:14px; color: var(--mut); margin:0; }
        button:focus-visible, select:focus-visible { outline:3px solid #231F1C; outline-offset:2px; }

        .riwayat { background:#fff; border:1px solid var(--line); border-radius:16px; padding:24px; margin:16px 0 48px; box-shadow:0 4px 12px rgba(0,0,0,.05); }
        .rhead { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
        .rhead h2 { margin:0; font-size:18px; }
        .rhead select { font:inherit; font-size:14px; padding:6px 12px; border:1px solid var(--line); border-radius:10px; background:#fff; }
        .avg { font-size:14px; color: var(--mut); }
        .tablewrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { text-align:left; font-weight:600; color:var(--mut); padding:8px 12px; border-bottom:1px solid var(--line); white-space:nowrap; }
        td { padding:12px; border-bottom:1px solid var(--line); vertical-align:top; }
        td.mono { font-family: ui-monospace, Consolas, monospace; white-space:nowrap; }
        td.total { font-weight:700; }

        .sr { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
        #info { position:fixed; left:50%; bottom:24px; transform:translateX(-50%); background:var(--ink); color:#fff; padding:10px 20px; border-radius:20px; font-size:14px; font-weight:600; }
        #info.err { background: var(--late); }

        @media (max-width: 1023px) { .board { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 599px) { .wrap { padding: 0 16px; } .board { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <div class="wrap top">
            <span class="brand">Techno <span>POS</span></span>
            <span class="chip outlet">{{ $outletName }}</span>
            <span class="chip spacer">{{ $userName }} · Dapur</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-out">Keluar</button>
            </form>
        </div>
    </header>

    <main class="wrap">
        <section class="banner" aria-label="Info outlet">
            <div><h1>{{ $outletName }}</h1><small>Dapur · {{ $label }}</small></div>
            <span class="pill" id="status-data" role="status">Memuat…</span>
            <span class="pill">Cetak pesanan: lewat browser</span>
        </section>

        <section class="board" id="board" aria-label="Papan pesanan"></section>

        <section class="riwayat" aria-labelledby="judul-riwayat">
            <div class="rhead">
                <h2 id="judul-riwayat">Selesai (<span id="n-selesai">0</span>)</h2>
                <label class="sr" for="rentang">Rentang riwayat</label>
                <select id="rentang">
                    <option value="1">Hari ini</option>
                    <option value="7">7 hari terakhir</option>
                </select>
                <span class="avg" id="avg"></span>
            </div>
            <div class="tablewrap">
                <table>
                    <thead>
                        <tr>
                            <th>Pesanan</th><th>Menu</th><th>Antrian</th><th>Diambil</th>
                            <th>Dimasak</th><th>Serving</th><th>Total waktu</th><th>Selesai</th>
                        </tr>
                    </thead>
                    <tbody id="riwayat-body"></tbody>
                </table>
            </div>
            <p class="kosong" id="riwayat-kosong" hidden>Belum ada pesanan selesai.</p>
        </section>
    </main>

    <div id="info" role="status" aria-live="polite" hidden></div>

    <script>
        window.DAPUR = {
            urlData: @json(route('dapur.data')),
            urlMaju: @json(url('/dapur/pesanan')),
            outlet: @json($outletName)
        };
    </script>
    <script src="{{ asset('js/cetak.js') }}"></script>
    <script src="{{ asset('js/kasir.js') }}"></script>
    <script src="{{ asset('js/dapur.js') }}"></script>
</body>
</html>
