// There's some string sub done here before being loaded. It's meant to be odd
document.addEventListener("DOMContentLoaded", (event) => {
    window.altCookies.toast = document.getElementById('alt-cookies-consent-toast');
    window.altCookies.toastOverlay = document.getElementById('alt-cookies-consent-toast-overlay');
    window.altCookies.cookieLifetime = {{ cookie_lifetime }};

    // Consent itself is handled in alt-cookies-consent-default.js, which runs before any tag
    // loads rather than waiting for this event. All that's left to do here is show the toast
    // to anyone who hasn't chosen yet.
    if (window.altCookies.getAltCookie() === null) {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden')
    }
});
