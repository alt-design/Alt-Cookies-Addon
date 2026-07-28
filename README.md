# Alt Cookies Addon

> Easily manage consent for Google consent mode v2 and other optional tracking

## Features

This addon features:

- Ez Google Analytics controls
- Custom Cookie Lifetime
- Necessary, Analytics and Advertising Cookies fields
- Replaceable default consent popup

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require alt-design/alt-cookies
```

## Basic Use

To enable the default consent popup:

- Open up your main `Template.antlers.html` or equivalent
- Add our `{{ AltCookies:Toast }}` tag before the closing `</body>` tag
- Add a way to change the preferences, up to you here, but for example, `<button onclick="{{ AltCookies:reset }}">Cookie Preferences</button>`
- You're away!!

To configure Google Analytics : 

- Navigate to your `Control Panel > Alt Cookies`
- Head to the `Google` tab
- Simply enter your Google Tag ID
- Both single tags (`G-`, `AW-`, `DC-`) and Google Tag Manager containers (`GTM-`) are supported. The correct snippet is picked based on the prefix, so a `GTM-` id gets the full Tag Manager container snippet rather than the gtag.js one.
- Add our consent popup or make sure the Alt Cookies scripts are loaded if you're doing a custom popup

### Consent mode and third party tags

Consent defaults are written synchronously, immediately before the tag or container loads, based on the visitor's stored choice.

Google's own tags respect this out of the box. Third party tags in a container (Meta, TikTok and similar) do not. For those you need to open the tag in Tag Manager and, under `Advanced Settings > Consent Settings`, set `Require additional consent for tag to fire` with `ad_storage`. Without that, Tag Manager fires them regardless of consent state and there's nothing this addon can do about it from outside the container.

### The Tag Manager `noscript` iframe

Google's install instructions include a second `<noscript>` snippet alongside the container. We deliberately don't output it.

Consent mode lives entirely in JavaScript. The `noscript` iframe loads `ns.html` straight from Google with no `dataLayer` and no way to read the visitor's choice, so anything it fires does so without consent. It can't be gated either. This addon stores consent in a cookie written by JavaScript, so a visitor with JavaScript disabled can never have that cookie, never sees the banner, and has no way to consent in the first place. Gating the iframe on that cookie would mean it never renders for the only visitors who would ever use it, which is the same as leaving it out.

If you need it anyway, add it yourself immediately after the opening `<body>` tag in your layout, and understand that it is not covered by the consent banner:

``` html
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
```

It isn't needed for Tag Assistant to validate your container, and most modern tags (GA4 included) don't fire through it regardless.

To configure other tracking :

- Navigate to your `Control Panel > Alt Cookies`
- Head to the `General` tab
- Add your `<script>` tags for other tracking here in the appropriate boxes.
- Note that `Analytics` and `Advertising` can be toggled.
- These will then get put on page according to the consent level the user agreed to.
- `Necessary` cookies always appear on page
- WARNING : These fields place what you put in them onto the page. Please check for errors and make sure that the code you put in here is safe.


## Advanced Use

To build a custom cookie popup:

- Open up your master `Template.antlers.html` or equivalent
- Add our `{{ AltCookies:Scripts }}` tag to your popup view.
- You'll need an "Accept" and "Accept Necessary" button.
- The "Accept" button needs to have `{{ AltCookies:accept }}` in it's onclick
- The "Accept Necessary" button needs to have `{{ AltCookies:decline }}` in it's onclick
- You will then need at least 2 checkboxes to configure analytics and advertising cookies.
- The Analytics checkbox requires an id of `alt-cookies-analytics` for the Javascript to hook into
- The Analytics checkbox requires an id of `alt-cookies-advertising` for the Javascript to hook into
- If you just want to allow `Necessary` and `All` cookies as your options, then you could hide these checkboxes and give them the `checked` property. They just need to exist.

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

### Starter Kits
- [Alt Starter Kit](https://statamic.com/starter-kits/alt-design/alt-starter-kit) 

### Addons
- [Alt Redirect Addon](https://github.com/alt-design/Alt-Redirect-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)
- [Alt Google 2FA Addon](https://github.com/alt-design/Alt-Google-2fa-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House
Derby  
DE1 3EE
UK  

