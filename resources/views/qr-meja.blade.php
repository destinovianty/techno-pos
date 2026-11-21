<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Meja · {{ $outletName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, sans-serif; background:#F7F1E8; color:#231F1C; line-height:1.5; }
        .top { max-width:1100px; margin:0 auto; padding:24px; }
        h1 { margin:0 0 4px; font-size:24px; }
        .muted { font-size:14px; color:#6B5F55; margin:0 0 12px; }
        .warn { background:#FEF3C7; border:1px solid #F59E0B; border-radius:12px; padding:12px 16px; font-size:14px; margin:12px 0; }
        .warn code { background:#fff; padding:1px 6px; border-radius:6px; }
        .btn { min-height:44px; padding:0 20px; border:0; border-radius:12px; background:#231F1C; color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        .grid { max-width:1100px; margin:0 auto; padding:0 24px 48px; display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:16px; }
        .kartu { background:#fff; border:1px solid #E7DDD0; border-radius:16px; padding:20px; text-align:center; break-inside:avoid; }
        .kartu h2 { margin:0; font-size:15px; color:#6B5F55; font-weight:600; }
        .kartu .no { font-size:28px; font-weight:800; margin:2px 0 12px; }
        .qr { display:flex; justify-content:center; min-height:180px; align-items:center; font-size:12px; color:#6B5F55; }
        .kartu small { display:block; margin-top:10px; font-size:12px; color:#6B5F55; }
        @media print {
            body { background:#fff; }
            .noprint { display:none !important; }
            .grid { grid-template-columns:repeat(3, 1fr); padding:0; }
            .kartu { box-shadow:none; }
        }
    </style>
</head>
<body>
    <div class="top noprint">
        <h1>QR Meja · {{ $outletName }}</h1>
        <p class="muted">Cetak lalu tempel di tiap meja. Customer scan, pilih menu, dan pesanan langsung masuk ke dapur dan terlihat di kasir.</p>
        @if ($lokal)
            <div class="warn">
                <b>Perhatian:</b> alamat QR saat ini <code>{{ $base }}</code>. Alamat itu hanya bisa dibuka di komputer ini, tidak dari HP customer.
                Isi <code>QR_BASE_URL</code> di file <code>.env</code> dengan alamat yang bisa dijangkau HP (misalnya <code>http://192.168.1.10:8000</code>),
                lalu jalankan <code>php artisan config:clear</code> dan muat ulang halaman ini.
            </div>
        @else
            <p class="muted">Alamat QR: <code>{{ $base }}/…</code></p>
        @endif
        <button class="btn" type="button" onclick="window.print()">Cetak QR</button>
    </div>

    <div class="grid" id="grid"></div>

    <script>
        window.QRMEJA = {
            outlet: @json($outletName),
            base: @json($base),
            tables: @json($tables)
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/qr-meja.js') }}"></script>
</body>
</html>
