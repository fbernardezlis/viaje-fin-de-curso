(function (window) {
    'use strict';

    var cfg = window.VFC_PORTAL || {};
    var restRoot = (cfg.restRoot || '').replace(/\/+$/, '') + '/';
    var nonce = cfg.nonce || '';

    function request(path, options) {
        options = options || {};
        var headers = options.headers || {};
        headers['X-WP-Nonce'] = nonce;
        if (options.body && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }
        return fetch(restRoot + path.replace(/^\/+/, ''), {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: headers,
            body: options.body
        }).then(function (res) {
            if (!res.ok) {
                return res.json().then(function (err) {
                    throw err;
                });
            }
            return res.json();
        });
    }

    function formatEur(value) {
        var num = parseFloat(value || 0);
        return num.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20ac';
    }

    window.VFC_PORTAL = window.VFC_PORTAL || {};
    window.VFC_PORTAL.api = {
        get: function (path) { return request(path); },
        post: function (path, body) {
            return request(path, {
                method: 'POST',
                body: body ? JSON.stringify(body) : null
            });
        }
    };
    window.VFC_PORTAL.fmt = { eur: formatEur };
})(window);
