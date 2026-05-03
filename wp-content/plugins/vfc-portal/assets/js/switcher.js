(function (document) {
    'use strict';

    var sidebar = document.querySelector('[data-vfc-switcher]');
    if (!sidebar) return;
    sidebar.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var target = e.target.closest('.vfc-portal-list-item');
        if (target && target.tagName === 'A') {
            e.preventDefault();
            window.location.href = target.href;
        }
    });
})(document);
