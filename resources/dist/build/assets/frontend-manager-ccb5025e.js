window.altCookies={toast:null,cookieLifetime:30,consentLevel:0,userConsentGranted:function(){window.altCookies.buildConsentLevel(!0),window.altCookies.setAltCookie(),window.altCookies.hideToast(),location.reload()},buildConsentLevel:function(e){if(!e){window.altCookies.consentLevel=1;return}let i=1,n=document.getElementById("alt-cookies-analytics").checked?1:0,t=document.getElementById("alt-cookies-advertising").checked?2:0;window.altCookies.consentLevel=i+n+t},userConsentDenied:function(){window.altCookies.buildConsentLevel(!1),window.altCookies.denyGtagTracking(),window.altCookies.setAltCookie(),window.altCookies.hideToast(),location.reload()},denyGtagTracking:function(){typeof gtag>"u"||gtag("consent","default",{ad_storage:"denied",ad_user_data:"denied",ad_personalization:"denied",analytics_storage:"denied"})},hideToast:function(){window.altCookies.toast!==null&&(window.altCookies.toast.classList.add("alt-cookies-translate-y-full"),window.altCookies.toastOverlay.classList.add("alt-cookies-hidden"))},setAltCookie:function(){const e=new Date;e.setTime(e.getTime()+window.altCookies.cookieLifetime*24*60*60*1e3);let i="expires="+e.toUTCString();document.cookie="AltCookieAddon="+window.altCookies.consentLevel+";"+i+";path=/"},getAltCookie:function(){let e="AltCookieAddon=",n=decodeURIComponent(document.cookie).split(";");for(let t=0;t<n.length;t++){let o=n[t];for(;o.charAt(0)==" ";)o=o.substring(1);if(o.indexOf(e)==0)return o.substring(e.length,o.length)}return null},resetConsent:function(){window.altCookies.toast.classList.remove("alt-cookies-translate-y-full"),window.altCookies.toastOverlay.classList.remove("alt-cookies-hidden")}};
