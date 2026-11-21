(function () {
    // Lebar kertas printer: '58mm' atau '80mm'.
    const KERTAS = '80mm';

    const asli = window.open.bind(window);

    // Jendela kosong yang dibuka untuk mencetak diganti bingkai tersembunyi (tanpa pop-up).
    // Alamat web biasa tetap dibuka seperti semula.
    window.open = function (url, target, fitur) {
        if (url) return asli(url, target, fitur);

        const f = document.createElement('iframe');
        f.setAttribute('aria-hidden', 'true');
        f.tabIndex = -1;
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
        document.body.appendChild(f);

        const win = f.contentWindow;

        return {
            document: win.document,
            focus: function () { win.focus(); },
            print: function () {
                const d = win.document;
                const gaya = d.createElement('style');
                gaya.textContent = '@page{size:' + KERTAS + ' auto;margin:0}'
                    + 'html,body{width:100%!important;margin:0!important}'
                    + 'body{box-sizing:border-box;padding:2mm 3mm;font-size:' + (KERTAS === '58mm' ? '11px' : '13px') + '!important}';
                d.head.appendChild(gaya);
                win.focus();
                win.print();
                setTimeout(function () { f.remove(); }, 60000);
            },
            close: function () { f.remove(); },
        };
    };
})();