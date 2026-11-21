<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard PIC · POS Techno</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #DC143C;
            --primary-hover: #C61236;
            --primary-tint: #FBE3E8;
            --heading: #121212;
            --body: #4A4A4A;
            --surface: #F5F5FA;
            --border: #EAEAEA;
            --success: #1E7E34;
            --warn: #B45309;
            --radius: 12px;
            --shadow-1: 0 4px 12px rgba(0, 0, 0, .05);
            --shadow-2: 0 8px 24px rgba(0, 0, 0, .08);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.6; color: var(--body); background: var(--surface); -webkit-tap-highlight-color: transparent; }
        h1, h2, h3, h4 { font-family: Poppins, system-ui, sans-serif; font-weight: 600; color: var(--heading); margin: 0; line-height: 1.3; }
        h3 { font-size: 18px; line-height: 1.4; }
        [hidden] { display: none !important; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

        /* Header */
        header { background: #fff; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; }
        .top { display: flex; align-items: center; gap: 12px; min-height: 60px; flex-wrap: wrap; }
        .brand { font-family: Poppins, sans-serif; font-weight: 600; font-size: 20px; color: var(--heading); white-space: nowrap; }
        .sub { font-size: 14px; white-space: nowrap; }
        .spacer { margin-left: auto; }
        .chip { font-size: 13px; border: 1px solid var(--border); border-radius: 8px; padding: 4px 10px; background: #fff; white-space: nowrap; }
        .chip b { color: var(--heading); font-weight: 600; margin-left: 4px; }
        #status-data.live { color: var(--success); border-color: var(--success); }

        /* Tabs */
        nav { display: flex; gap: 4px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        nav::-webkit-scrollbar { display: none; }
        .tab { font: inherit; font-size: 15px; color: var(--body); background: none; border: 0; border-bottom: 2.5px solid transparent; padding: 10px 14px; cursor: pointer; white-space: nowrap; transition: color 0.15s, border-color 0.15s; }
        .tab:hover { color: var(--heading); }
        .tab[aria-selected="true"] { color: var(--heading); font-weight: 600; border-bottom-color: var(--primary); }

        /* Buttons & inputs */
        .btn { font: inherit; font-weight: 600; font-size: 14px; height: 42px; padding: 0 16px; border-radius: var(--radius); border: 1.5px solid var(--primary); background: var(--primary); color: #fff; cursor: pointer; transition: background 0.15s; }
        .btn:hover { background: var(--primary-hover); }
        .btn:disabled { opacity: .6; cursor: wait; }
        .btn.secondary { background: transparent; color: var(--primary); }
        .btn.secondary:hover { background: rgba(220, 20, 60, .05); }
        .btn.wide { width: 100%; height: 48px; }
        .btn.small { height: 34px; padding: 0 12px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 4px; color: var(--heading); }
        select, input[type=number], input[type=text] { width: 100%; height: 46px; font: inherit; font-size: 16px; padding: 0 14px; border: 1px solid #E5E5E5; border-radius: var(--radius); background: var(--surface); color: var(--heading); margin-bottom: 16px; }
        select:focus, input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(220, 20, 60, .2); }
        .actions { display: flex; flex-direction: column; gap: 8px; }
        .controls { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 20px; }
        .controls .seg { display: flex; gap: 8px; }
        .controls .field { min-width: 200px; }
        .controls .field.narrow { min-width: 150px; max-width: 190px; }
        .controls select, .controls input[type=number] { margin-bottom: 0; height: 42px; }

        /* Layout */
        main { padding: 20px 0 48px; }
        .grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
        .c3 { grid-column: span 3; } .c4 { grid-column: span 4; } .c5 { grid-column: span 5; } .c7 { grid-column: span 7; } .c8 { grid-column: span 8; } .c12 { grid-column: span 12; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-1); transition: box-shadow .15s; }
        .card:hover { box-shadow: var(--shadow-2); }
        .card h3 { margin-bottom: 14px; }
        .stack { display: flex; flex-direction: column; gap: 20px; }
        .kpi .label { font-size: 13px; color: #666; }
        .kpi .value { font-family: Poppins, sans-serif; font-weight: 600; font-size: 26px; line-height: 1.3; color: var(--heading); word-break: break-word; }
        .kpi .value.good { color: var(--success); }
        .kpi .value.warn { color: var(--warn); }

        /* Charts */
        .bars { display: flex; align-items: flex-end; gap: clamp(4px, 1.5vw, 16px); height: 160px; }
        .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; font-size: 11px; }
        .bar { width: 100%; border-radius: 8px; background: var(--primary-tint); }
        .bar.now { background: var(--primary); }
        .chartscroll { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
        .bars.jam { gap: 8px; height: 200px; }
        .bars.jam .bar-col { font-size: 10px; min-width: 26px; }
        .hbar { display: grid; grid-template-columns: 130px 1fr 100px; align-items: center; gap: 12px; font-size: 14px; margin-bottom: 10px; }
        .hbar .track { height: 8px; }
        .hbar .fill { height: 8px; border-radius: 4px; background: var(--primary); }
        .hbar .fill.green { background: var(--success); }
        .hbar .num { text-align: right; }
        .legend { display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px; margin: 0 0 12px; }
        .legend i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }

        /* Tables */
        .tablewrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; font-weight: 600; color: var(--primary); background: var(--primary-tint); padding: 8px 14px; white-space: nowrap; }
        th:first-child { border-radius: 8px 0 0 8px; } th:last-child { border-radius: 0 8px 8px 0; }
        td { padding: 10px 14px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td.low { color: var(--warn); font-weight: 600; }
        td.ok { color: var(--success); font-weight: 600; }
        tr.total td { font-weight: 600; color: var(--heading); border-bottom: 0; background: var(--surface); }
        tr.ramai td { background: #FFF7ED; }
        .badge { display: inline-block; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        .badge.pending { background: #FEF3C7; color: var(--warn); }
        .badge.done { background: #DCFCE7; color: var(--success); }
        .linkbtn { font: inherit; font-size: 14px; background: none; border: 0; color: var(--primary); cursor: pointer; padding: 0; text-decoration: underline; }
        tr.detail td { background: var(--surface); white-space: normal; }
        .pesan { font-size: 14px; margin: 12px 0 0; min-height: 22px; }
        .pesan.err { color: #BA1A1A; } .pesan.good { color: var(--success); }
        .note { font-size: 13px; margin: 14px 0 0; color: #777; }
        .empty { font-size: 14px; margin: 0; }
        button:focus-visible { outline: 3px solid #121212; outline-offset: 2px; }

        @media (max-width: 1023px) {
            .grid { gap: 16px; }
            .c3 { grid-column: span 6; }
            .c4, .c5, .c7, .c8 { grid-column: span 12; }
        }
        @media (max-width: 767px) {
            .wrap { padding: 0 14px; }
            .top { padding: 8px 0; min-height: auto; gap: 8px; justify-content: space-between; }
            .brand { font-size: 18px; }
            .sub { font-size: 12px; }
            .chip { font-size: 12px; padding: 3px 8px; }
            .btn.secondary { height: 34px; padding: 0 10px; font-size: 13px; }
            .hbar { grid-template-columns: 90px 1fr 80px; gap: 8px; font-size: 13px; }
            .controls { flex-direction: column; align-items: stretch; gap: 10px; }
            .controls .field, .controls .field.narrow { min-width: 100%; max-width: none; width: 100%; }
            .controls .seg { width: 100%; }
            .controls .seg .btn { flex: 1; }
        }
        @media (max-width: 480px) {
            .grid { gap: 10px; }
            .card { padding: 14px; border-radius: 10px; }
            .kpi .value { font-size: 20px; }
            .kpi .label { font-size: 12px; }
            table { font-size: 13px; }
            th, td { padding: 8px 10px; }
        }
        @media (max-width: 360px) {
            .c3 { grid-column: span 12; }
        }
    </style>
</head>
<body>
    <header>
        <div class="wrap">
            <div class="top">
                <span class="brand">POS Techno</span>
                <span class="sub">Multi Outlet</span>
                <span class="spacer chip">Peran <b>PIC</b></span>
                <span class="chip">Outlet <b>Semua</b></span>
                <span class="chip" id="status-data" role="status">Memuat…</span>
                <span class="sub">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn secondary">Keluar</button>
                </form>
            </div>
            <nav role="tablist" aria-label="Menu PIC">
                <button class="tab" role="tab" id="t-dasbor" data-panel="dasbor" aria-selected="true">Dasbor</button>
                <button class="tab" role="tab" id="t-jam" data-panel="jam" aria-selected="false">Jam Ramai</button>
                <button class="tab" role="tab" id="t-stok" data-panel="stok" aria-selected="false">Stok &amp; Transfer</button>
                <button class="tab" role="tab" id="t-harga" data-panel="harga" aria-selected="false">Produk &amp; Harga</button>
                <button class="tab" role="tab" id="t-laba" data-panel="laba" aria-selected="false">Laba</button>
                <button class="tab" role="tab" id="t-supplier" data-panel="supplier" aria-selected="false">Supplier</button>
                <button class="tab" role="tab" id="t-transaksi" data-panel="transaksi" aria-selected="false">Transaksi</button>
            </nav>
        </div>
    </header>

    <main class="wrap">

        {{-- ========== DASBOR ========== --}}
        <section id="dasbor" role="tabpanel" aria-labelledby="t-dasbor">
            <div class="grid">
                <div class="card kpi c3"><div class="label">Penjualan hari ini</div><div class="value" id="k-jual">-</div></div>
                <div class="card kpi c3"><div class="label">Transaksi</div><div class="value" id="k-trx">-</div></div>
                <div class="card kpi c3"><div class="label">Laba kotor hari ini</div><div class="value good" id="k-laba">-</div></div>
                <div class="card kpi c3"><div class="label">Stok menipis</div><div class="value" id="k-low">-</div></div>

                <div class="card c7"><h3>Tren 7 Hari (juta rupiah)</h3><div class="bars" id="tren"></div></div>
                <div class="card c5"><h3>Produk Terlaris (30 hari)</h3><div id="laris"></div></div>

                <div class="card c12">
                    <h3>Perbandingan Outlet · Hari Ini</h3>
                    <div class="tablewrap"><table>
                        <thead><tr><th>Outlet</th><th>Penjualan</th><th>Laba kotor</th><th>Transaksi</th><th>Stok menipis</th></tr></thead>
                        <tbody id="banding"></tbody>
                    </table></div>
                </div>
            </div>
        </section>

        {{-- ========== JAM RAMAI (PENDAPATAN PER JAM) ========== --}}
        <section id="jam" role="tabpanel" aria-labelledby="t-jam" hidden>
            <div class="controls">
                <div class="seg" role="group" aria-label="Rentang data">
                    <button type="button" class="btn" data-rentang="hari">Hari ini</button>
                    <button type="button" class="btn secondary" data-rentang="rata7">Rata-rata 7 hari</button>
                </div>
                <div class="field"><label for="pj-outlet">Outlet</label><select id="pj-outlet"></select></div>
                <div class="field narrow"><label for="pj-kap">Transaksi / jam per karyawan</label><input type="number" id="pj-kap" min="1" step="1"></div>
                <div class="field narrow"><label for="pj-staf">Karyawan bertugas</label><input type="number" id="pj-staf" min="1" step="1"></div>
            </div>

            <div class="grid">
                <div class="card kpi c3"><div class="label">Jam tersibuk</div><div class="value" id="pj-puncak">-</div></div>
                <div class="card kpi c3"><div class="label">Pendapatan jam tersibuk</div><div class="value good" id="pj-omzet">-</div></div>
                <div class="card kpi c3"><div class="label">Jumlah jam ramai</div><div class="value" id="pj-ramai">-</div></div>
                <div class="card kpi c3"><div class="label">Tambahan karyawan (maks.)</div><div class="value warn" id="pj-tambah">-</div></div>

                <div class="card c12">
                    <h3>Pendapatan per Jam</h3>
                    <p class="legend" id="pj-legend"></p>
                    <div class="chartscroll"><div class="bars jam" id="pj-chart"></div></div>
                    <p class="empty" id="pj-kosong" hidden>Belum ada transaksi pada rentang ini.</p>
                </div>

                <div class="card c12">
                    <h3>Rincian per Jam &amp; Saran Karyawan</h3>
                    <div class="tablewrap"><table>
                        <thead><tr><th>Jam</th><th>Pendapatan</th><th>Transaksi</th><th>Kebutuhan karyawan</th><th>Saran</th></tr></thead>
                        <tbody id="pj-body"></tbody>
                    </table></div>
                    <p class="note" id="pj-note"></p>
                </div>
            </div>
        </section>

        {{-- ========== STOK & TRANSFER ========== --}}
        <section id="stok" role="tabpanel" aria-labelledby="t-stok" hidden>
            <div class="grid">
                <div class="card c8">
                    <h3>Stok per Outlet</h3>
                    <div class="tablewrap"><table>
                        <thead id="stok-head"></thead>
                        <tbody id="stok-body"></tbody>
                    </table></div>
                    <p class="note">Angka oranye berarti stok 5 atau kurang. Tanda "-" berarti outlet itu tidak menjual produknya. Stok habis? Buka tab Supplier untuk memesan.</p>
                </div>
                <div class="card c4">
                    <h3>Transfer Stok Antar Outlet</h3>
                    <form id="form-transfer">
                        <label for="dari">Dari</label><select id="dari"></select>
                        <label for="ke">Ke</label><select id="ke"></select>
                        <label for="produk">Produk</label><select id="produk"></select>
                        <label for="jumlah">Jumlah</label><input type="number" id="jumlah" min="1" value="5">
                        <button type="submit" class="btn wide" id="btn-transfer">Transfer</button>
                        <p id="pesan-transfer" class="pesan" role="status" aria-live="polite"></p>
                    </form>
                </div>
            </div>
        </section>

        {{-- ========== PRODUK & HARGA (HPP) ========== --}}
        <section id="harga" role="tabpanel" aria-labelledby="t-harga" hidden>
            <div class="grid">
                <div class="card c8">
                    <h3>HPP, Harga Jual, Margin &amp; Stok</h3>
                    <div class="controls">
                        <div class="field"><label for="f-outlet-harga">Stok untuk outlet</label><select id="f-outlet-harga"></select></div>
                    </div>
                    <div class="tablewrap"><table>
                        <thead><tr><th>Produk</th><th>HPP</th><th>Harga jual</th><th>Laba / unit</th><th>Margin</th><th>Stok</th><th></th></tr></thead>
                        <tbody id="harga-body"></tbody>
                    </table></div>
                    <p class="note">Margin = (harga jual − HPP) ÷ harga jual. Margin di bawah 30% ditandai oranye. Perubahan harga berlaku untuk semua outlet yang menjual produk itu, dan tidak mengubah transaksi yang sudah terjadi.</p>
                </div>
                <div class="card c4" style="align-self:start">
                    <h3>Ubah HPP &amp; Harga Jual</h3>
                    <form id="form-harga">
                        <label for="h-produk">Produk</label><select id="h-produk"></select>
                        <label for="h-hpp">HPP (Rp)</label><input type="number" id="h-hpp" min="0" step="100">
                        <label for="h-jual">Harga jual (Rp)</label><input type="number" id="h-jual" min="0" step="100">
                        <button type="submit" class="btn wide" id="btn-harga">Simpan</button>
                        <p id="pesan-harga" class="pesan" role="status" aria-live="polite"></p>
                    </form>
                </div>
            </div>
        </section>

        {{-- ========== LAPORAN LABA ========== --}}
        <section id="laba" role="tabpanel" aria-labelledby="t-laba" hidden>
            <div class="controls">
                <div class="seg" role="group" aria-label="Periode laporan">
                    <button type="button" class="btn" data-periode="harian">Harian</button>
                    <button type="button" class="btn secondary" data-periode="mingguan">Mingguan</button>
                    <button type="button" class="btn secondary" data-periode="bulanan">Bulanan</button>
                </div>
                <div class="field"><label for="l-outlet">Outlet</label><select id="l-outlet"></select></div>
            </div>
            <div class="grid">
                <div class="card kpi c3"><div class="label">Penjualan</div><div class="value" id="l-jual">-</div></div>
                <div class="card kpi c3"><div class="label">HPP</div><div class="value" id="l-hpp">-</div></div>
                <div class="card kpi c3"><div class="label">Laba kotor</div><div class="value good" id="l-laba">-</div></div>
                <div class="card kpi c3"><div class="label">Margin laba</div><div class="value" id="l-margin">-</div></div>

                <div class="card c7">
                    <h3 id="l-judul">Laba per Outlet</h3>
                    <div class="tablewrap"><table>
                        <thead><tr><th>Outlet</th><th>Penjualan</th><th>HPP</th><th>Laba kotor</th><th>Margin</th></tr></thead>
                        <tbody id="l-body"></tbody>
                    </table></div>
                    <p class="note">Laba kotor = penjualan − HPP. Biaya operasional (gaji, sewa, listrik) belum dihitung.</p>
                </div>
                <div class="card c5"><h3>Perbandingan Laba</h3><div id="l-chart"></div></div>
            </div>
        </section>

        {{-- ========== SUPPLIER ========== --}}
        <section id="supplier" role="tabpanel" aria-labelledby="t-supplier" hidden>
            <div class="grid">
                <div class="c7 stack">
                    <div class="card">
                        <h3>Perlu Dipesan</h3>
                        <div class="tablewrap"><table>
                            <thead><tr><th>Outlet</th><th>Produk</th><th>Stok</th><th>Supplier</th><th></th></tr></thead>
                            <tbody id="perlu-body"></tbody>
                        </table></div>
                        <p id="perlu-kosong" class="empty" hidden>Semua stok aman, tidak ada yang perlu dipesan.</p>
                    </div>
                    <div class="card">
                        <h3>Riwayat Pesanan ke Supplier</h3>
                        <div class="tablewrap"><table>
                            <thead><tr><th>No</th><th>Supplier</th><th>Outlet</th><th>Produk</th><th>Jumlah</th><th>Status</th><th></th></tr></thead>
                            <tbody id="po-body"></tbody>
                        </table></div>
                    </div>
                </div>
                <div class="card c5" style="align-self:start">
                    <h3>Pesan ke Supplier</h3>
                    <form id="form-po">
                        <label for="po-supplier">Supplier</label><select id="po-supplier"></select>
                        <label for="po-outlet">Outlet tujuan</label><select id="po-outlet"></select>
                        <label for="po-produk">Produk</label><select id="po-produk"></select>
                        <label for="po-jumlah">Jumlah</label><input type="number" id="po-jumlah" min="1" value="20">
                        <label for="po-catatan">Catatan (opsional)</label><input type="text" id="po-catatan" placeholder="Contoh: kirim sebelum jam 10">
                        <div class="actions">
                            <button type="submit" class="btn wide" id="btn-po">Kirim Pesanan</button>
                            <button type="button" id="po-wa" class="btn secondary wide">Kirim lewat WhatsApp</button>
                        </div>
                        <p id="pesan-po" class="pesan" role="status" aria-live="polite"></p>
                    </form>
                </div>
            </div>
        </section>

        {{-- ========== TRANSAKSI ========== --}}
        <section id="transaksi" role="tabpanel" aria-labelledby="t-transaksi" hidden>
            <div class="card">
                <h3>Riwayat Transaksi · Semua Outlet</h3>
                <div class="tablewrap"><table>
                    <thead><tr><th>No</th><th>Outlet</th><th>Jam</th><th>Metode</th><th>Total</th><th></th></tr></thead>
                    <tbody id="trx-body"></tbody>
                </table></div>
            </div>
        </section>
    </main>

    <script>
        window.PIC = {
            urlData: @json(route('pic.data')),
            urlProduk: @json(route('pic.produk')),
            urlTransfer: @json(route('pic.transfer')),
            urlPesanan: @json(route('pic.pesanan')),
            urlTerima: @json(url('/pic/pesanan'))
        };
    </script>
    <script src="{{ asset('js/pic.js') }}"></script>
</body>
</html>
