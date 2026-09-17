/*
 * Control panel behaviour for the cookie scan tab.
 *
 * The tab is rendered through the html fieldtype, so its markup sits inside the
 * publish form that Vue manages and is replaced whenever Vue re-renders. Every
 * handler is therefore delegated from the document rather than bound to an element.
 */
(function () {
    function panel() {
        return document.querySelector('[data-alt-cookies-panel]');
    }

    function showError(message) {
        const target = document.querySelector('[data-alt-cookies-error]');

        if (! target) {
            return;
        }

        target.textContent = message;
        target.hidden = false;
    }

    function post(url, button) {
        const container = panel();

        if (! container) {
            return;
        }

        const original = button.textContent;

        button.disabled = true;
        button.textContent = 'Working...';

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': container.getAttribute('data-alt-cookies-csrf'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, body: body };
                });
            })
            .then(function (result) {
                if (! result.ok) {
                    throw new Error(result.body.message || 'The request failed.');
                }

                window.location.reload();
            })
            .catch(function (error) {
                button.disabled = false;
                button.textContent = original;
                showError(error.message);
            });
    }

    document.addEventListener('click', function (event) {
        const action = event.target.closest('[data-alt-cookies-action]');

        if (action) {
            const container = panel();

            if (! container) {
                return;
            }

            const url = action.getAttribute('data-alt-cookies-action') === 'clear'
                ? container.getAttribute('data-alt-cookies-clear-url')
                : container.getAttribute('data-alt-cookies-scan-url');

            post(url, action);

            return;
        }

        const copy = event.target.closest('[data-alt-cookies-copy]');

        if (! copy) {
            return;
        }

        const format = copy.getAttribute('data-alt-cookies-copy');
        const source = document.querySelector('[data-alt-cookies-policy="' + format + '"]');
        const confirmation = document.querySelector('[data-alt-cookies-copied]');

        if (! source) {
            return;
        }

        navigator.clipboard.writeText(source.value).then(function () {
            if (! confirmation) {
                return;
            }

            confirmation.hidden = false;
            setTimeout(function () { confirmation.hidden = true; }, 2000);
        });
    });
})();
