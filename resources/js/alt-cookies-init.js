document.addEventListener("DOMContentLoaded", (event) => {
    window.altCookies.toast = document.getElementById('alt-cookies-consent-toast');
    window.altCookies.toastOverlay = document.getElementById('alt-cookies-consent-toast-overlay');
    window.altCookies.cookieLifetime = {{ cookie_lifetime }};

    if (window.altCookies.getAltCookie() === null) {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden')
    }
});
