/*
 * Control panel behaviour for the cookie scan tab.
 *
 * The tab is rendered through the html fieldtype, so its markup sits inside the
 * publish form that Vue manages and is replaced whenever Vue re-renders. Every
 * handler is therefore delegated from the document rather than bound to an element.
 */
(function () {
    const CONSENT_COOKIE = 'AltCookieAddon';
    const FULL_CONSENT = '4';
    const LOAD_TIMEOUT = 15000;
    const SETTLE_POLL = 500;
    const SETTLE_MAX = 8000;

    function panel() {
        return document.querySelector('[data-alt-cookies-panel]');
    }

    function cookieNames() {
        return document.cookie
            .split(';')
            .map(function (pair) { return pair.trim().split('=')[0]; })
            .filter(Boolean);
    }

    function readCookie(name) {
        const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));

        return match ? match[2] : null;
    }

    function writeCookie(name, value) {
        document.cookie = name + '=' + value + '; path=/; max-age=600';
    }

    function forgetCookie(name) {
        const host = window.location.hostname;

        document.cookie = name + '=; path=/; max-age=0';
        document.cookie = name + '=; path=/; domain=' + host + '; max-age=0';
        document.cookie = name + '=; path=/; domain=.' + host + '; max-age=0';
    }

    function say(message) {
        const target = document.querySelector('[data-alt-cookies-status]');

        if (! target) {
            return;
        }

        target.textContent = message;
        target.hidden = ! message;
    }

    function showError(message) {
        const target = document.querySelector('[data-alt-cookies-error]');

        if (! target) {
            return;
        }

        target.textContent = message;
        target.hidden = false;
    }

    function headers(container) {
        return {
            'X-CSRF-TOKEN': container.getAttribute('data-alt-cookies-csrf'),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };
    }

    function post(url, button) {
        const container = panel();

        if (! container) {
            return;
        }

        const original = button.textContent;

        button.disabled = true;
        button.textContent = 'Working...';

        fetch(url, { method: 'POST', credentials: 'same-origin', headers: headers(container) })
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

    /**
     * Wait until the cookie jar stops changing, or until we give up on it.
     *
     * Third party tags load their own scripts from their own hosts, so a cookie can
     * appear several seconds after the page fires load. A fixed pause is either too
     * short for those or wastes time on a page that sets nothing.
     */
    function settle() {
        return new Promise(function (resolve) {
            let previous = cookieNames().join('|');
            let waited = 0;

            const timer = setInterval(function () {
                waited += SETTLE_POLL;

                const current = cookieNames().join('|');
                const quiet = current === previous;

                previous = current;

                if ((quiet && waited >= SETTLE_POLL * 2) || waited >= SETTLE_MAX) {
                    clearInterval(timer);
                    resolve();
                }
            }, SETTLE_POLL);
        });
    }

    /**
     * Load one page out of sight and let its scripts run.
     *
     * Resolves either way. A page the site refuses to frame, through X-Frame-Options
     * or a frame-ancestors policy, still fires load, so a refusal looks the same as a
     * page that set nothing. That is reported as nothing found rather than as an error.
     */
    function visit(url) {
        return new Promise(function (resolve) {
            const frame = document.createElement('iframe');

            frame.setAttribute('aria-hidden', 'true');
            frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:1024px;height:768px;border:0';

            let done = false;

            function finish() {
                if (done) {
                    return;
                }

                done = true;

                settle().then(function () {
                    frame.remove();
                    resolve();
                });
            }

            frame.addEventListener('load', finish);
            setTimeout(finish, LOAD_TIMEOUT);

            frame.src = url;
            document.body.appendChild(frame);
        });
    }

    async function deepScan(button) {
        const container = panel();

        if (! container) {
            return;
        }

        let pages = [];

        try {
            pages = JSON.parse(container.getAttribute('data-alt-cookies-pages') || '[]');
        } catch (error) {
            pages = [];
        }

        if (! pages.length) {
            showError('There are no pages to visit. Run a scan first.');

            return;
        }

        const original = button.textContent;
        const previousConsent = readCookie(CONSENT_COOKIE);
        const found = [];
        const caused = [];

        button.disabled = true;
        writeCookie(CONSENT_COOKIE, FULL_CONSENT);

        // Taken after granting consent, so the cookie recording that choice is not
        // reported back as something one of the pages set.
        const before = cookieNames();

        try {
            for (let i = 0; i < pages.length; i++) {
                button.textContent = 'Visiting ' + (i + 1) + ' of ' + pages.length;
                say('Loading ' + pages[i] + ' and letting its scripts run.');

                await visit(pages[i]);

                cookieNames().forEach(function (name) {
                    if (before.indexOf(name) !== -1 || caused.indexOf(name) !== -1) {
                        return;
                    }

                    caused.push(name);
                    found.push({ name: name, url: pages[i] });
                });
            }
        } finally {
            caused.forEach(forgetCookie);

            if (previousConsent === null) {
                forgetCookie(CONSENT_COOKIE);
            } else {
                writeCookie(CONSENT_COOKIE, previousConsent);
            }
        }

        say('Recording ' + found.length + ' ' + (found.length === 1 ? 'cookie' : 'cookies') + '.');

        try {
            const response = await fetch(container.getAttribute('data-alt-cookies-observed-url'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: Object.assign({ 'Content-Type': 'application/json' }, headers(container)),
                body: JSON.stringify({ cookies: found }),
            });

            const body = await response.json();

            if (! response.ok) {
                throw new Error(body.message || 'The results could not be recorded.');
            }

            window.location.reload();
        } catch (error) {
            button.disabled = false;
            button.textContent = original;
            say('');
            showError(error.message);
        }
    }

    document.addEventListener('click', function (event) {
        const action = event.target.closest('[data-alt-cookies-action]');

        if (action) {
            const container = panel();

            if (! container) {
                return;
            }

            const kind = action.getAttribute('data-alt-cookies-action');

            if (kind === 'deep') {
                deepScan(action);

                return;
            }

            post(
                kind === 'clear'
                    ? container.getAttribute('data-alt-cookies-clear-url')
                    : container.getAttribute('data-alt-cookies-scan-url'),
                action
            );

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
