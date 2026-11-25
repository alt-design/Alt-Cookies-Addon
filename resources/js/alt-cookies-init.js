document.addEventListener("DOMContentLoaded", (event) => {
    window.altCookies.toast = document.getElementById('alt-cookies-consent-toast');
    window.altCookies.toastOverlay = document.getElementById('alt-cookies-consent-toast-overlay');
    window.altCookies.cookieLifetime = {{ cookie_lifetime }};
    window.altCookies.consentEvent = {{ consent_event_name }};

    // So an editor can add an ordinary link to the cookie policy page rather than
    // needing an onclick they cannot write from a rich text field
    document.addEventListener('click', function (clickEvent) {
        let trigger = clickEvent.target.closest('a[href="#cookie-preferences"], [data-alt-cookies-preferences]');

        if (! trigger) {
            return;
        }

        clickEvent.preventDefault();
        window.altCookies.showPreferences(trigger);
    });

    // The banner links to the policy, and opening over it would stop a first-time visitor reading it
    const policyUrl = {{ policy_url }};
    const pageAddress = (url) => url.origin + url.pathname.replace(/\/$/, '');
    const isPolicyPage = () => {
        try {
            return pageAddress(new URL(policyUrl, location.href)) === pageAddress(location);
        } catch (error) {
            return false;
        }
    };
    const onPolicyPage = policyUrl !== null && isPolicyPage();

    if (window.altCookies.getAltCookie() === null && ! onPolicyPage) {
        window.altCookies.showToast();
    }
});
