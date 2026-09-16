# Alt Cookies Addon

> Easily manage consent for Google consent mode v2 and other optional tracking

## Features

This addon features:

- Ez Google Analytics controls
- Custom Cookie Lifetime
- Necessary, Analytics and Advertising Cookies fields
- Replaceable default consent popup
- A cookie scan that reports what your site actually sets, and what it likely sets

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


## Scanning for cookies

Writing a cookie policy usually means opening dev tools on a few pages, noting down cookie
names and then looking each one up. The scan does that part for you.

Go to `Control Panel > Alt Cookies > Scan` and press **Scan this site**. It requests a
sample of your own pages, as a visitor who accepted every category, and reports two things.

**Observed** cookies were set by the server and read out of the response headers. These are
confirmed. You will normally see your Laravel session cookie, the CSRF token, and anything
your host sets, such as Cloudflare's bot management cookie.

**Likely** cookies come from third party services recognised in the page markup and in the
Necessary, Analytics and Advertising fields on the settings page. A scan cannot run
JavaScript, so a Meta Pixel or a Hotjar snippet never gets the chance to set anything. What
the scan can tell you is that the service is there, and what it is documented to set once a
real browser loads the page.

Around thirty services are recognised, including Google Analytics, Google Ads, Meta, Hotjar,
LinkedIn, TikTok, Microsoft Clarity, HubSpot, YouTube, Vimeo, Stripe and Intercom. Anything
it does not recognise is listed on its own rather than quietly dropped, so you know what is
left to look up.

Two things the scan cannot see, both worth knowing:

- **Tag manager containers.** Google Tag Manager sets no cookies itself, it loads whatever
  tags are configured in the container. Those have to be checked in Tag Manager.
- **Cookies set only after an interaction**, such as a video the visitor has to press play on.

### Configuration

The defaults suit most sites. To change them, publish the config:

``` bash
php artisan vendor:publish --tag=alt-cookies-config
```

| Key | Default | What it does |
| --- | --- | --- |
| `scan.max_pages` | 15 | The most pages one scan will request |
| `scan.per_collection` | 5 | The most entries taken from any one collection |
| `scan.timeout` | 10 | Seconds to wait for each page |
| `scan.verify_ssl` | true | Verify the TLS certificate of the site being scanned |
| `scan.user_agent` | `AltCookiesScanner/1.0` | Sent so the requests are identifiable in your logs |

Each has an environment variable, so you do not have to publish the config to change one:
`ALT_COOKIES_SCAN_MAX_PAGES`, `ALT_COOKIES_SCAN_PER_COLLECTION`, `ALT_COOKIES_SCAN_TIMEOUT`
and `ALT_COOKIES_SCAN_VERIFY_SSL`.

**Local sites served over https** use a development certificate that PHP does not trust, so
every page in the scan will fail to fetch. Set `ALT_COOKIES_SCAN_VERIFY_SSL=false` in your
local `.env`, and nowhere else.

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

## Development

The addon is developed against a throwaway Statamic site built from our starter kit, with
the addon wired in as a Composer path repository so edits are live.

``` bash
statamic new alt-cookies-dev alt-design/alt-starter-kit
cd alt-cookies-dev
composer config repositories.alt-cookies path ../Alt-Cookies-Addon
composer require "alt-design/alt-cookies:*@dev"
```

The starter kit pins `php` to `^8.3` in its `composer.json`, but Statamic 6 pulls in Symfony 8
which needs 8.4. Correct the constraint before serving it locally, or the site will fatal on
a platform check. It also needs Node 20 or later, because Tailwind 4 ships a native binding
that will not build on 18.

After changing anything in `resources/css` or `resources/dist`, republish:

``` bash
php artisan vendor:publish --tag=alt-cookies --force
```

### Tests

``` bash
composer install
composer test
```

Pest, running against Orchestra Testbench. Tests write collections, entries and users into
`tests/__fixtures__`, and clean up after themselves.

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

