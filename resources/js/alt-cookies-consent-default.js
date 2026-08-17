// Runs synchronously before any Google tag loads
(function () {
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;

    // The one place a consent level becomes consent mode signals. The frontend manager reuses
    // this for the 'update' call when the visitor actually chooses.
    window.altCookiesConsentFor = function (level) {
        var analytics = (level === '2' || level === '4') ? 'granted' : 'denied';
        var advertising = (level === '3' || level === '4') ? 'granted' : 'denied';

        return {
            'ad_storage': advertising,
            'ad_user_data': advertising,
            'ad_personalization': advertising,
            'analytics_storage': analytics
        };
    };

    var match = document.cookie.match(/(?:^|;\s*)AltCookieAddon=([^;]*)/);

    gtag('consent', 'default', window.altCookiesConsentFor(match ? match[1] : null));
})();
