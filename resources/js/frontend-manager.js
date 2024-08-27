// the main man
window.altCookies = {
    toast : null, // Toast element storage (set during init)
    cookieLifetime : 30, //default to 30
    consentLevel : 0,
    // Called with the accept button
    userConsentGranted : function ()
    {
        window.altCookies.buildConsentLevel(true);
        window.altCookies.setAltCookie();
        window.altCookies.hideToast();
        location.reload();
    },
    simpleConsentGranted : function ()
    {
        window.altCookies.consentLevel = 4;
        window.altCookies.setAltCookie();
        window.altCookies.hideToast();
        location.reload();
    },
    buildConsentLevel : function (accepted)
    {
        if (!accepted) {
            window.altCookies.consentLevel = 1;
            return;
        }
        let necessary = 1
        let analytics =  (document.getElementById("alt-cookies-analytics")).checked ? 1 : 0;
        let advertising =  (document.getElementById("alt-cookies-advertising")).checked ? 2 : 0;

        window.altCookies.consentLevel = necessary + analytics + advertising;
    },
    // Called with the decline button
    userConsentDenied : function ()
    {
        window.altCookies.buildConsentLevel(false);
        window.altCookies.denyGtagTracking();
        window.altCookies.setAltCookie();
        window.altCookies.hideToast();
        location.reload();
    },
    denyGtagTracking: function ()
    {
        if(typeof gtag === 'undefined') {
            return;
        }

        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied'
        });
    },
    hideToast : function()
    {
        if (window.altCookies.toast === null) {
            return;
        }
        window.altCookies.toast.classList.add('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.add('alt-cookies-hidden')
    },
    // Set the cookie with a boolean
    setAltCookie : function ()
    {
        const d = new Date();
        d.setTime(d.getTime() + (window.altCookies.cookieLifetime*24*60*60*1000));
        let expires = "expires="+ d.toUTCString();
        document.cookie = "AltCookieAddon=" + window.altCookies.consentLevel + ";" + expires + ";path=/";
    },
    // Get the value from our cookie
    getAltCookie: function ()
    {
        let name = "AltCookieAddon=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for(let i = 0; i <ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return null;
    },
    eraseAltCookie: function () {
        document.cookie = 'AltCookieAddon=; Max-Age=-99999999;';
    },
    // Invalidate the cookie and deny google tracking on reset
    resetConsent : function()
    {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full');
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden');
        window.altCookies.buildConsentLevel(false);
        window.altCookies.denyGtagTracking();
        window.altCookies.eraseAltCookie();
        location.reload();
    },
}
