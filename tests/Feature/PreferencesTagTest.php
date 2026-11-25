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

it('clears the consent cookie for the whole site on reset, not just the current path', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js');

    expect(substr_count($script, 'document.cookie ='))->toBe(1)
        ->and($script)->toContain("document.cookie = 'AltCookieAddon=' + value + '; expires=' + expires.toUTCString() + '; path=/';")
        ->and($script)->toContain("window.altCookies.writeAltCookie('', new Date(0));");
});

it('binds the cookie preferences link in the script it puts on the page', function () {
    $init = (new AltCookies)->init();

    expect($init)
        ->toContain('a[href="#cookie-preferences"]')
        ->toContain('data-alt-cookies-preferences')
        ->toContain('window.altCookies.showPreferences(trigger)');
});
