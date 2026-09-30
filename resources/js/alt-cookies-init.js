document.addEventListener("DOMContentLoaded", (event) => {
    window.altCookies.toast = document.getElementById('alt-cookies-consent-toast');
    window.altCookies.toastOverlay = document.getElementById('alt-cookies-consent-toast-overlay');
    window.altCookies.cookieLifetime = {{ cookie_lifetime }};

    // So an editor can add an ordinary link to the cookie policy page rather than
    // needing an onclick they cannot write from a rich text field
    document.addEventListener('click', function (clickEvent) {
        let trigger = clickEvent.target.closest('a[href="#cookie-preferences"], [data-alt-cookies-preferences]');

        if (! trigger) {
            return;
        }

        clickEvent.preventDefault();
        window.altCookies.showPreferences();
    });

    if (window.altCookies.getAltCookie() === null) {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden')
    }
});
