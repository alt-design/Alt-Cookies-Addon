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

Also - check out our other addons!
- [Alt Redirect Addon](https://github.com/alt-design/Alt-Redirect-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House
Derby  
DE1 3EE
UK  

