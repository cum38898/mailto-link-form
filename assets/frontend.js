(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.classList || !form.classList.contains('malifo-frontend-form')) {
            return;
        }

        var helpText = form.querySelector('.malifo-help-text');
        if (!helpText) {
            return;
        }

        helpText.classList.remove('is-hidden');
    });
}());
