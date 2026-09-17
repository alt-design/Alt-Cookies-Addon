<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cookie scan
    |--------------------------------------------------------------------------
    |
    | Settings for the control panel cookie scan, which requests pages from
    | this site and reports the cookies they set.
    |
    */

    'scan' => [

        // The most pages a single scan will request.
        'max_pages' => (int) env('ALT_COOKIES_SCAN_MAX_PAGES', 15),

        // The most entries taken from any one collection, so a large collection
        // cannot fill the whole scan on its own.
        'per_collection' => (int) env('ALT_COOKIES_SCAN_PER_COLLECTION', 5),

        // Seconds to wait for each page.
        'timeout' => (int) env('ALT_COOKIES_SCAN_TIMEOUT', 10),

        // Verify the TLS certificate of the site being scanned. Local domains
        // served by Herd or Valet use a certificate PHP does not trust, so this
        // can be turned off for local development only.
        'verify_ssl' => (bool) env('ALT_COOKIES_SCAN_VERIFY_SSL', true),

        // After requesting the pages, load them in the control panel's own browser so
        // the cookies JavaScript sets are observed rather than inferred. Turn this off
        // where the control panel is served from a different domain to the front end,
        // or where the site refuses to be framed.
        'run_in_browser' => (bool) env('ALT_COOKIES_SCAN_IN_BROWSER', true),

        // Sent as the User-Agent so these requests are identifiable in logs.
        'user_agent' => 'AltCookiesScanner/1.0 (+https://github.com/alt-design/Alt-Cookies-Addon)',

    ],

];
