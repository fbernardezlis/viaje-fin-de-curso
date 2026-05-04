(function (document) {
    'use strict';

    document.querySelectorAll('[data-vfc-qr]').forEach(function (card) {
        var matriculaId = parseInt(card.getAttribute('data-matricula-id'), 10);
        if (!matriculaId) return;

        var showBtn = card.querySelector('[data-vfc-qr-show]');
        var resendBtn = card.querySelector('[data-vfc-qr-resend]');
        var copyUrlBtn = card.querySelector('[data-vfc-qr-copy-url]');
        var urlInput = card.querySelector('.vfc-portal-qr-url-input');
        var imgWrap = card.querySelector('.vfc-portal-qr-image');
        var img = card.querySelector('img[data-vfc-qr-img-src]');

        if (showBtn && imgWrap && img) {
            showBtn.addEventListener('click', function () {
                var src = img.getAttribute('data-vfc-qr-img-src');
                if (!src) return;
                img.src = src;
                imgWrap.hidden = false;
            });
        }

        if (copyUrlBtn && urlInput) {
            copyUrlBtn.addEventListener('click', function () {
                var v = urlInput.value;
                if (!v) return;
                var reset = function (label) {
                    copyUrlBtn.textContent = label;
                };
                var orig = copyUrlBtn.dataset.originalLabel || copyUrlBtn.textContent;
                copyUrlBtn.dataset.originalLabel = orig;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(v).then(function () {
                        copyUrlBtn.textContent = 'Copiado';
                        setTimeout(function () { reset(orig); }, 2000);
                    }).catch(function () {
                        urlInput.select();
                        document.execCommand('copy');
                        copyUrlBtn.textContent = 'Copiado';
                        setTimeout(function () { reset(orig); }, 2000);
                    });
                } else {
                    urlInput.select();
                    document.execCommand('copy');
                    copyUrlBtn.textContent = 'Copiado';
                    setTimeout(function () { reset(orig); }, 2000);
                }
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
