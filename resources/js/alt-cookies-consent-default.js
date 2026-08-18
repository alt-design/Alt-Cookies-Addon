// Runs synchronously before any Google tag loads
(function () {
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;

    window.altCookiesConsent = function (analytics, advertising) {
        return {
            'ad_storage': advertising,
            'ad_user_data': advertising,
            'ad_personalization': advertising,
            'analytics_storage': analytics
        };
    };

    window.altCookiesConsentFor = function (level) {
        return window.altCookiesConsent(
            (level === '2' || level === '4') ? 'granted' : 'denied',
            (level === '3' || level === '4') ? 'granted' : 'denied'
        );
    };

    gtag('consent', 'default', window.altCookiesConsent('{{ default_analytics_consent }}', '{{ default_advertising_consent }}'));

    var match = document.cookie.match(/(?:^|;\s*)AltCookieAddon=([^;]*)/);

    if (match) {
        gtag('consent', 'update', window.altCookiesConsentFor(match[1]));
    }
})();
