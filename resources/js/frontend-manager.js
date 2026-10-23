// the main man
window.altCookies = {
    toast : null, // Toast element storage (set during init)
    cookieLifetime : 30, //default to 30
    consentLevel : 0,
    dialogOpen : false,
    returnFocusTo : null,
    inertElements : [],
    pageObserver : null,
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
    showToast : function (returnFocusTo = document.activeElement)
    {
        if (window.altCookies.toast === null) {
            return;
        }
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden')

        if (window.altCookies.isModal() && ! window.altCookies.dialogOpen) {
            window.altCookies.openDialog(returnFocusTo);
        }
    },
    hideToast : function()
    {
        if (window.altCookies.toast === null) {
            return;
        }
        window.altCookies.toast.classList.add('alt-cookies-translate-y-full')
        window.altCookies.toastOverlay.classList.add('alt-cookies-hidden')

        if (window.altCookies.dialogOpen) {
            window.altCookies.closeDialog();
        }
    },
    isModal : function ()
    {
        return window.altCookies.toast.getAttribute('aria-modal') === 'true';
    },
    openDialog : function (returnFocusTo)
    {
        window.altCookies.dialogOpen = true;
        window.altCookies.returnFocusTo = returnFocusTo;
        window.altCookies.makePageInert();
        document.addEventListener('keydown', window.altCookies.handleDialogKeys);
        window.altCookies.focusableInToast()[0]?.focus();
    },
    closeDialog : function ()
    {
        window.altCookies.dialogOpen = false;
        document.removeEventListener('keydown', window.altCookies.handleDialogKeys);
        window.altCookies.releasePage();
        window.altCookies.restoreFocus();
    },
    makePageInert : function ()
    {
        window.altCookies.inertElements = [];
        window.altCookies.pageObserver = new MutationObserver(window.altCookies.makeAddedElementsInert);

        for (let node = window.altCookies.toastOverlay; node !== document.body && node.parentElement !== null; node = node.parentElement) {
            Array.from(node.parentElement.children).forEach(function (sibling) {
                if (sibling !== node) {
                    window.altCookies.makeInert(sibling);
                }
            });
            window.altCookies.pageObserver.observe(node.parentElement, { childList: true });
        }
    },
    makeAddedElementsInert : function (mutations)
    {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE && ! node.contains(window.altCookies.toastOverlay)) {
                    window.altCookies.makeInert(node);
                }
            });
        });
    },
    makeInert : function (element)
    {
        if (element.hasAttribute('inert')) {
            return;
        }
        element.setAttribute('inert', '');
        window.altCookies.inertElements.push(element);
    },
    releasePage : function ()
    {
        window.altCookies.pageObserver?.disconnect();
        window.altCookies.pageObserver = null;
        window.altCookies.inertElements.forEach(function (element) {
            element.removeAttribute('inert');
        });
        window.altCookies.inertElements = [];
    },
    focusableInToast : function ()
    {
        let candidates = window.altCookies.toast.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        return Array.from(candidates).filter(function (element) {
            return element.getClientRects().length > 0;
        });
    },
    // Escape only closes the panel when there is already a choice to fall back on
    handleDialogKeys : function (event)
    {
        if (event.key === 'Escape' && window.altCookies.getAltCookie() !== null) {
            window.altCookies.hideToast();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        let focusable = window.altCookies.focusableInToast();
        let first = focusable[0];
        let last = focusable[focusable.length - 1];
        let current = document.activeElement;
        let inside = window.altCookies.toast.contains(current);

        if (first === undefined) {
            event.preventDefault();
            return;
        }

        if (event.shiftKey && (current === first || ! inside)) {
            event.preventDefault();
            last.focus();
            return;
        }

        if (! event.shiftKey && (current === last || ! inside)) {
            event.preventDefault();
            first.focus();
        }
    },
    restoreFocus : function ()
    {
        let target = window.altCookies.returnFocusTo;
        window.altCookies.returnFocusTo = null;

        if (target && target !== document.body && target.isConnected) {
            target.focus();

            if (document.activeElement === target) {
                return;
            }
        }

        window.altCookies.focusTopOfPage();
    },
    // The body is not focusable on its own, so it is made focusable for long enough
    // to move the starting point for Tab back to the top of the page
    focusTopOfPage : function ()
    {
        document.body.setAttribute('tabindex', '-1');
        document.body.focus({ preventScroll: true });
        document.body.removeAttribute('tabindex');
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
        window.altCookies.showToast();
        window.altCookies.buildConsentLevel(false);
        window.altCookies.denyGtagTracking();
        window.altCookies.eraseAltCookie();
        location.reload();
    },
}
