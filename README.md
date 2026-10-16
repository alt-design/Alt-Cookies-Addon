# Alt Cookies Addon

> Easily manage consent for Google consent mode v2 and other optional tracking

## Features

This addon features:

- Ez Google Analytics controls
- Custom Cookie Lifetime
- Necessary, Analytics and Advertising Cookies fields
- Replaceable default consent popup
- A cookie scan that reports what your site actually sets, and what it likely sets
- Cookie policy copy generated from the scan

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require alt-design/alt-cookies
```

## Basic Use

To enable the default consent popup:

- Open up your main `Template.antlers.html` or equivalent
- Add our `{{ AltCookies:Toast }}` tag before the closing `</body>` tag
- Add a way to change the preferences, see below
- You're away!!

### Letting people change their mind

The addon listens for clicks on any link whose href is `#cookie-preferences`, so an editor
can add an ordinary link anywhere in page content, typically on the cookie policy page:

``` html
<a href="#cookie-preferences">Change your cookie preferences</a>
```

That reopens the panel with their current choices already ticked, and leaves those choices
alone if they close it again. `data-alt-cookies-preferences` on any element does the same,
for a button that is not a link.

From a template, `{{ AltCookies:preferences }}` returns the call for an `onclick`:

``` html
<button onclick="{{ AltCookies:preferences }}">Cookie preferences</button>
```

`{{ AltCookies:reset }}` is still there and still does what it always did, which is throw
the choices away and reload. Prefer `preferences` for a link people are meant to use.

### Only the categories you use

The panel shows the Analytics and Advertising choices only when that category is enabled
on the `General` tab. If you publish your own copy of the consent view, wrap each choice
the same way; the script counts a missing checkbox as declined.

``` html
{{ if {AltCookies:enabled category="advertising"} }}
    <input type="checkbox" name="Advertising" id="alt-cookies-advertising" ... />
{{ /if }}
```

To configure Google Analytics : 

- Navigate to your `Control Panel > Alt Cookies`
- Head to the `Google` tab
- Simply enter your Google Tag ID
- Both single tags (`G-`, `AW-`, `DC-`) and Google Tag Manager containers (`GTM-`) are supported. The correct snippet is picked based on the prefix, so a `GTM-` id gets the full Tag Manager container snippet rather than the gtag.js one.
- Add our consent popup or make sure the Alt Cookies scripts are loaded if you're doing a custom popup

### Consent mode and third party tags

Consent is written synchronously, immediately before the tag or container loads. The baseline goes out as a consent mode `default`, then a stored choice follows straight after as an `update`, in the same script so nothing can fire in between.

The baseline sent before the visitor answers is denied for everything. If your site has a lawful basis for tracking without consent, the `Google` tab has `Consent Mode Defaults`, where each signal is set to `Granted` or `Denied`, and you should match those to the defaults configured in your GA or Tag Manager property. Note these are not the `By Default` toggles on the `General` tab, which only decide whether a checkbox starts ticked.

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

### How consent is applied

`Analytics` and `Advertising` are output on every page inside an inert `<template>`, so
nothing in them runs or loads until the visitor agrees. When they accept, the snippets for
the categories they chose are switched on in place, without reloading the page. Returning
visitors get theirs switched on as the page loads. Scripts run in the order they are
written, and an external script without `async` finishes loading before the next one runs,
so a snippet that loads a library and then calls it works as it would inline.

Because the page is the same whatever a visitor has chosen, it is safe to serve from
Statamic's full static cache.

Two things this changes:

- Taking consent away still reloads the page, because a script that is already running
  cannot be unloaded. Giving consent, or adding a category, does not.
- The snippets run after the page has parsed rather than as the `<head>` is read. On the
  page where a visitor first accepts, `DOMContentLoaded` and `load` have already happened,
  so code in a snippet that waits for either runs from the next page onwards. Avoid
  `document.write` in these fields: run after parsing, it replaces the whole page. The
  standard vendor snippets (Google, Hotjar, Meta, LinkedIn and similar) load asynchronously
  and are unaffected.


## Scanning for cookies

Writing a cookie policy usually means opening dev tools on a few pages, noting down cookie
names and then looking each one up. The scan does that part for you.

Open `Control Panel > Alt Cookies`, switch to the **Scan** tab and press **Scan this site**.
The tab sits alongside General and Google, on the same page as the rest of the addon's
settings.

A scan runs in two passes, because neither on its own is complete:

1. **It requests each page and reads the response headers.** This is how cookies the server
   sets are seen, including the `HttpOnly` ones no script can read, and it is the only pass
   that sees a cookie's attributes: `Secure`, `HttpOnly`, `SameSite` and its real lifetime.
2. **It then loads each page in your browser and lets the scripts run.** This is how the
   cookies JavaScript sets are seen: Google Analytics, Hotjar, Meta, Stripe and the rest. A
   browser only hands over a name and value, so these carry no attributes.

Results say which pass saw each cookie. What is left over is reported as likely.

**Observed** cookies were set by the server and read out of the response headers. These are
confirmed. You will normally see your Laravel session cookie, the CSRF token, and anything
your host sets, such as Cloudflare's bot management cookie.

**Likely** cookies are the ones neither pass can reach: those set on another company's
domain. DoubleClick's `IDE`, Facebook's `fr`, LinkedIn's `bcookie`. No page can read those,
by design. They are recognised from the third party services found in the page markup and in
the Necessary, Analytics and Advertising fields, and reported as what each service is
documented to set.

Around thirty services are recognised, including Google Analytics, Google Ads, Meta, Hotjar,
LinkedIn, TikTok, Microsoft Clarity, HubSpot, YouTube, Vimeo, Stripe and Intercom. Anything
it does not recognise is listed on its own rather than quietly dropped, so you know what is
left to look up.

Two things the scan cannot see, both worth knowing:

- **Tag manager containers.** Google Tag Manager sets no cookies itself, it loads whatever
  tags are configured in the container. Those have to be checked in Tag Manager.
- **Cookies set only after an interaction**, such as a video the visitor has to press play on.

### What running a scan costs

The second pass loads the pages as a visitor who accepted everything, so the tracking runs
for real. **Your analytics will record a visit for each page, from you, and the cookies are
set in your browser.** The cookies are removed afterwards and your own consent choice is put
back. The analytics hits cannot be taken back.

On a busy site that is noise. On a quiet one it is visible in the numbers, so run it
deliberately rather than casually.

To run the header pass on its own, set `ALT_COOKIES_SCAN_IN_BROWSER=false`. Worth doing
where the control panel is served from a different domain to the front end, or where the
site refuses to be framed, since the second pass cannot work in either case. A page that
refuses to be framed is reported rather than quietly skipped.

### On the dashboard

There is a dashboard widget showing the last scan and a button to run another. Statamic
does not let an addon put itself on the dashboard, so add it in `config/statamic/cp.php`:

``` php
'widgets' => [
    ['type' => 'alt_cookies_scan', 'width' => 50],
],
```

### Cookie policy copy

The Scan tab writes the results up as policy copy, in Markdown or HTML, for pasting into
your cookie policy page. Observed and likely cookies are merged, because a policy describes
what may be set rather than what one request happened to see.

Two things worth knowing about how it categorises:

- **A cookie is filed under the category that gates the service setting it, not its own.**
  LinkedIn documents `bscookie` as necessary because LinkedIn needs it. On a site that only
  loads LinkedIn once advertising consent is given, it is an advertising cookie, and a
  policy saying otherwise is wrong.
- **Anything the scan could not identify is marked** `Describe this cookie before
  publishing.` rather than left blank, so it cannot go out unnoticed.

**Copy as HTML** puts the copy on the clipboard as HTML as well as plain text, so pasting
it into a Bard or rich text field gives real headings and tables rather than the markup as
text. **Copy as Markdown** is plain text, for a file or a Markdown field.

Two things to set on the field you paste into, or the paste comes out wrong in ways that
are easy to miss:

- **`table` in `buttons`.** Bard only keeps the node types its buttons allow, so a field
  without it drops the tables and leaves the headings behind.
- **`enable_paste_rules: false`.** Bard's Markdown paste rules run over pasted text and
  read a cookie name such as `_ga_*` as italics, storing it as `ga*`. Verified against
  Statamic 6: it happens whether or not the name is wrapped in `<code>`, and it hits most
  Google Analytics cookies. Turning paste rules off on that field leaves the names alone.
  `enable_input_rules` can stay on, since that only affects typing.

This is a starting point and not legal advice. Read it, edit the wording, and keep your own
intro and contact details around it.

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
| `scan.run_in_browser` | true | Run the second pass, loading the pages in the control panel |
| `scan.user_agent` | `AltCookiesScanner/1.0` | Sent so the requests are identifiable in your logs |

Each has an environment variable, so you do not have to publish the config to change one:
`ALT_COOKIES_SCAN_MAX_PAGES`, `ALT_COOKIES_SCAN_PER_COLLECTION`, `ALT_COOKIES_SCAN_TIMEOUT`,
`ALT_COOKIES_SCAN_VERIFY_SSL` and `ALT_COOKIES_SCAN_IN_BROWSER`.

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

The addon's own front end assets are built with Vite, which needs Node 20 or later. The
committed build in `resources/dist` is what sites get, so rebuild it in the same commit as
any change to `resources/js/frontend-manager.js` or `resources/css`:

``` bash
npm install
npm run build
```

`resources/js/cp.js` and `resources/js/alt-cookies-init.js` are served as they are rather
than bundled, so they need no build step.

After changing anything in `resources/css` or `resources/dist`, republish into the dev site:

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

