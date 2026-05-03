(function (document) {
    'use strict';

    document.querySelectorAll('[data-vfc-qr]').forEach(function (card) {
        var matriculaId = parseInt(card.getAttribute('data-matricula-id'), 10);
        if (!matriculaId) return;

        var showBtn = card.querySelector('[data-vfc-qr-show]');
        var resendBtn = card.querySelector('[data-vfc-qr-resend]');
        var imgWrap = card.querySelector('.vfc-portal-qr-image');
        var img = card.querySelector('img[data-vfc-qr-img-src]');

        if (showBtn && imgWrap && img) {
            showBtn.addEventListener('click', function () {
                var src = img.getAttribute('data-vfc-qr-img-src');
                if (!src) return;
                img.src = src + (src.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
                imgWrap.hidden = false;
                showBtn.textContent = showBtn.dataset.refreshLabel || showBtn.textContent;
                showBtn.dataset.refreshLabel = 'Actualizar QR';
                showBtn.textContent = showBtn.dataset.refreshLabel;
            });
        }

        if (resendBtn) {
            resendBtn.addEventListener('click', function () {
                resendBtn.disabled = true;
                resendBtn.dataset.original = resendBtn.dataset.original || resendBtn.textContent;
                resendBtn.textContent = 'Enviando...';
                window.VFC_PORTAL.api.post('qr/resend', { matricula_id: matriculaId })
                    .then(function () {
                        resendBtn.textContent = 'Enviado \u2713';
                        setTimeout(function () {
                            resendBtn.disabled = false;
                            resendBtn.textContent = resendBtn.dataset.original;
                        }, 2500);
                    })
                    .catch(function () {
                        resendBtn.textContent = 'Error';
                        setTimeout(function () {
                            resendBtn.disabled = false;
                            resendBtn.textContent = resendBtn.dataset.original;
                        }, 2500);
                    });
            });
        }
    });
})(document);
