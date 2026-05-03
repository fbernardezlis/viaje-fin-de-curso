(function (window, document) {
    'use strict';

    var node = document.querySelector('[data-vfc-saldo]');
    if (!node) return;
    var alumnoId = parseInt(node.getAttribute('data-alumno-id'), 10);
    if (!alumnoId) return;

    function refresh() {
        window.VFC_PORTAL.api.get('saldo?alumno_id=' + alumnoId)
            .then(function (data) {
                var keys = ['confirmado', 'bloqueado', 'liquidado'];
                keys.forEach(function (key) {
                    var el = document.querySelectorAll('[data-key="' + key + '"]');
                    if (!el) return;
                    var value = data && data.saldo ? data.saldo[key] : 0;
                    el.forEach(function (n) { n.textContent = window.VFC_PORTAL.fmt.eur(value); });
                });
            })
            .catch(function () { /* silent */ });
    }

    setTimeout(refresh, 1500);
    setInterval(refresh, 30000);
})(window, document);
