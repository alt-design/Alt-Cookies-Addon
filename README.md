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

### Banner without the overlay

By default the popup dims the whole page and takes every tap until a choice is made. To show a compact banner along the bottom of the screen instead, with no backdrop, pass `overlay="false"`:

``` antlers
{{ AltCookies:Toast overlay="false" }}
```

- The page behind the banner stays scrollable and tappable, and keyboard focus is never trapped.
- The banner is a labelled `region` landmark rather than a modal.
- While it is showing, the page keeps clear of it so the end of the page and any focused element are not hidden underneath: `html` gets bottom padding and scroll padding of `--alt-cookies-banner-height`. This replaces any bottom padding your own CSS sets on `html` for as long as the banner is visible.
- The height is measured from the banner itself and kept up to date as the window or text size changes. The CSS value (14rem, or 8rem on viewports shorter than 620px or narrower than 480px) is only the fallback if JavaScript is unavailable, and you can override it in your own CSS.
- The padding rule uses `:has()`, which current browsers support. In a browser without it the banner still works, but the page does not reserve the space.
- It works with both the standard and the simple popup, and the option is per tag, so you can use it on selected templates only (for example landing pages) and leave the rest of the site on the default.
- Leaving the parameter out keeps the existing overlay behaviour.
- On viewports shorter than 620px or narrower than 480px the banner collapses to fit: the heading is hidden (the region keeps its label), the text is 14px, the three category checkboxes share one row and the two buttons sit side by side, with no category hidden or pre-ticked.
- The banner never takes more than 60% of the screen height. If its content needs more, for example at high zoom or with large text, it scrolls within the banner.
- The banner sits at `z-index: 80`, below most modal dialogs, so a modal opened from the page covers it. Set `--alt-cookies-banner-z` in your own CSS to raise it, for example above a sticky header. Fixed elements near the bottom of the screen that need to stay clear of it need their own spacing.
- For keyboard and screen reader users, place the tag straight after the opening `<body>` tag. The banner is fixed to the bottom of the screen whatever its place in the source, and putting it first means it is reached before the rest of the page rather than after the footer. The tag also outputs the consent scripts, so moving it moves them too: check the order of any scripts you rely on before you do.

To configure Google Analytics : 

- Navigate to your `Control Panel > Alt Cookies`
- Head to the `Google` tab
- Simply enter your Google Tag ID
- Add our consent popup or make sure the Alt Cookies scripts are loaded if you're doing a custom popup

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

