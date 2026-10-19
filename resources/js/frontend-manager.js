// the main man
window.altCookies = {
    toast : null, // Toast element storage (set during init)
    cookieLifetime : 30, //default to 30
    consentLevel : 0,
    // Called with the accept button
    userConsentGranted : function ()
    {
        window.altCookies.buildConsentLevel(true);
        window.altCookies.applyConsentLevel();
    },
    simpleConsentGranted : function ()
    {
        window.altCookies.consentLevel = 4;
        window.altCookies.applyConsentLevel();
    },
    // A script that is already running cannot be unloaded, so taking consent away still
    // needs a fresh page. Giving it only needs the newly allowed snippets switching on.
    applyConsentLevel : function ()
    {
        let previousLevel = window.altCookies.getAltCookie();

        window.altCookies.updateConsent();
        window.altCookies.setAltCookie();
        window.altCookies.hideToast();

        if (window.altCookies.withdrawsConsent(previousLevel, String(window.altCookies.consentLevel))) {
            location.reload();
            return;
        }

        window.altCookies.activateConsentedScripts(String(window.altCookies.consentLevel));
    },
    grants : function (level, category)
    {
        if (category === 'analytics') {
            return level === '2' || level === '4';
        }

        if (category === 'advertising') {
            return level === '3' || level === '4';
        }

        return false;
    },
    withdrawsConsent : function (previousLevel, newLevel)
    {
        return ['analytics', 'advertising'].some(function (category) {
            return window.altCookies.grants(previousLevel, category)
                && ! window.altCookies.grants(newLevel, category);
        });
    },
    activateConsentedScripts : function (level = window.altCookies.getAltCookie())
    {
        let templates = document.querySelectorAll('template[data-alt-cookies-category]:not([data-alt-cookies-active])');

        templates.forEach(function (template) {
            if (! window.altCookies.grants(level, template.dataset.altCookiesCategory)) {
                return;
            }

            template.dataset.altCookiesActive = '';
            window.altCookies.insertInOrder(Array.from(template.content.cloneNode(true).childNodes), template);
        });
    },
    // Added scripts run the moment they are inserted and external ones load async, so a
    // snippet that loads a library and then calls it has to wait for each external script
    insertInOrder : function (nodes, template)
    {
        let node = nodes.shift();

        if (node === undefined || template.parentNode === null) {
            return;
        }

        let insertNext = function () {
            window.altCookies.insertInOrder(nodes, template);
        };

        if (node.nodeName !== 'SCRIPT') {
            window.altCookies.insertWithNestedScripts(node, template, insertNext);
            return;
        }

        let script = window.altCookies.runnableCopy(node);

        if (! window.altCookies.blocksWhatFollows(node)) {
            template.parentNode.insertBefore(script, template);
            insertNext();
            return;
        }

        script.addEventListener('load', insertNext);
        script.addEventListener('error', insertNext);
        template.parentNode.insertBefore(script, template);
    },
    insertWithNestedScripts : function (node, template, insertNext)
    {
        let nested = node.querySelectorAll ? Array.from(node.querySelectorAll('script')) : [];
        let waitingOn = 0;

        let finishOne = function () {
            waitingOn--;

            if (waitingOn === 0) {
                insertNext();
            }
        };

        nested.forEach(function (script) {
            let copy = window.altCookies.runnableCopy(script);

            if (window.altCookies.blocksWhatFollows(script)) {
                waitingOn++;
                copy.addEventListener('load', finishOne);
                copy.addEventListener('error', finishOne);
            }

            script.replaceWith(copy);
        });

        template.parentNode.insertBefore(node, template);

        if (waitingOn === 0) {
            insertNext();
        }
    },
    blocksWhatFollows : function (script)
    {
        let runsAsJavaScript = ! script.type || /javascript|ecmascript/i.test(script.type);

        return script.hasAttribute('src')
            && runsAsJavaScript
            && ! script.hasAttribute('async')
            && ! script.hasAttribute('defer');
    },
    runnableCopy : function (script)
    {
        let copy = document.createElement('script');

        Array.from(script.attributes).forEach(function (attribute) {
            copy.setAttribute(attribute.name, attribute.value);
        });

        copy.async = script.hasAttribute('async');
        copy.text = script.text;

        return copy;
    },
    // Google needs the consent change signalled on the page it happened on, before the reload.
    // Reloading into a new 'default' alone loses the transition, and the data from before it.
    updateConsent : function ()
    {
        if (typeof gtag === 'undefined' || typeof window.altCookiesConsentFor !== 'function') {
            return;
        }

        gtag('consent', 'update', window.altCookiesConsentFor(String(window.altCookies.consentLevel)));
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
        window.altCookies.applyConsentLevel();
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
        let match = document.cookie.match(/(?:^|;\s*)AltCookieAddon=([^;]*)/);

        return match ? match[1] : null;
    },
    eraseAltCookie: function () {
        document.cookie = 'AltCookieAddon=; Max-Age=-99999999;';
    },
    // Tick the boxes to match what the visitor has already agreed to, so reopening
    // the panel shows their choices rather than resetting them
    applyStoredChoices : function ()
    {
        let cookie = window.altCookies.getAltCookie();

        if (cookie === null) {
            return;
        }

        let analytics = document.getElementById("alt-cookies-analytics");
        let advertising = document.getElementById("alt-cookies-advertising");

        if (analytics) {
            analytics.checked = window.altCookies.grants(cookie, 'analytics');
        }

        if (advertising) {
            advertising.checked = window.altCookies.grants(cookie, 'advertising');
        }
    },
    // Reopen the panel so the visitor can change their mind. Unlike resetConsent
    // this keeps their current choices and does not reload the page
    showPreferences : function ()
    {
        if (window.altCookies.toast === null) {
            return;
        }

        window.altCookies.applyStoredChoices();
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full');
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden');
    },
    // Invalidate the cookie and revoke consent on reset
    resetConsent : function()
    {
        window.altCookies.toast.classList.remove('alt-cookies-translate-y-full');
        window.altCookies.toastOverlay.classList.remove('alt-cookies-hidden');
        window.altCookies.buildConsentLevel(false);
        window.altCookies.updateConsent();
        window.altCookies.eraseAltCookie();
        location.reload();
    },
}

window.altCookies.activateConsentedScripts();
