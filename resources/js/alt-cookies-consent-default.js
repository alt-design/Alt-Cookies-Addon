// Runs synchronously before any Google tag loads. It has to be inline and it has to read the
// cookie in JS rather than PHP, otherwise a statically cached page bakes in one visitor's consent.
(function () {
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;

    var match = document.cookie.match(/(?:^|;\s*)AltCookieAddon=([^;]*)/);
    var level = match ? match[1] : null;
    var analytics = (level === '2' || level === '4') ? 'granted' : 'denied';
    var advertising = (level === '3' || level === '4') ? 'granted' : 'denied';

    gtag('consent', 'default', {
        'ad_storage': advertising,
        'ad_user_data': advertising,
        'ad_personalization': advertising,
        'analytics_storage': analytics
    });

    // Stops the frontend manager pushing a second, contradictory default once the DOM is ready
    window.altCookiesConsentDefaultSet = true;
})();
