// There's some string sub done here before being loaded. It's meant to be odd
document.addEventListener("DOMContentLoaded", (event) => {
    window.altCookies.toast = document.getElementById('alt-cookies-consent-toast');
    window.altCookies.toastOverlay = document.getElementById('alt-cookies-consent-toast-overlay');
    window.altCookies.cookieLifetime = {{ cookie_lifetime }};
    window.altCookies.denyGtagTracking();
    let cookie = window.altCookies.getAltCookie();
    // Show the toast if they're new / expired
    if ( cookie === null) {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden')
        return;
    }

    if(typeof gtag === 'undefined') {
        return;
    }

    switch(cookie) {
      // Necessary and Analytics
        case "2":
            gtag('consent', 'update', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'granted'
            });
            console.log('Analytics consent')
            break;
      // Necessary and Advertising
        case "3":
            gtag('consent', 'update', {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'denied'
            });

            console.log('Advertising Consent')
            break;
      // Necessary, Analytics and Advertising
        case "4":
            gtag('consent', 'update', {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'granted'
            });

            console.log('All Consent')
            break;
      // Accounts for necessary which doesn't need anything
        default:
            gtag('consent', 'update', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied'
            });

            console.log('All Consent Denied')

            break;
    }
});
