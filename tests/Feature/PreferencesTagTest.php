<?php

use AltDesign\AltCookiesAddon\Tags\AltCookies;

beforeEach(function () {
    $this->writeSettings(['cookie_lifetime' => 30]);
});

it('returns the call a template can put in an onclick', function () {
    expect((new AltCookies)->preferences())
        ->toBe('window.altCookies.showPreferences()');
});

it('leaves the older reset tag alone', function () {
    expect((new AltCookies)->reset())
        ->toBe('window.altCookies.resetConsent()');
});

it('binds the cookie preferences link in the script it puts on the page', function () {
    $init = (new AltCookies)->init();

    expect($init)
        ->toContain('a[href="#cookie-preferences"]')
        ->toContain('data-alt-cookies-preferences')
        ->toContain('window.altCookies.showPreferences()');
});
