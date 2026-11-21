(function () {
    const C = window.QRMEJA;
    const grid = document.getElementById('grid');

    for (let i = 1; i <= C.tables; i++) {
        const url = C.base + '/' + i;

        const kartu = document.createElement('div');
        kartu.className = 'kartu';

        const h = document.createElement('h2');
        h.textContent = C.outlet;
        kartu.appendChild(h);

        const no = document.createElement('div');
        no.className = 'no';
        no.textContent = 'Meja ' + i;
        kartu.appendChild(no);

        const qr = document.createElement('div');
        qr.className = 'qr';
        kartu.appendChild(qr);

        const kecil = document.createElement('small');
        kecil.textContent = 'Scan untuk memesan';
        kartu.appendChild(kecil);

        grid.appendChild(kartu);

        if (window.QRCode) {
            new QRCode(qr, { text: url, width: 180, height: 180 });
        } else {
            qr.textContent = 'QR tidak dapat dimuat. Periksa koneksi internet.';
        }
    }
})();
