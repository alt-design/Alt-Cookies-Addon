<?php

use AltDesign\AltCookiesAddon\Tags\AltCookies;

beforeEach(function () {
    $this->writeSettings([
        'necessary' => '<script src="https://example.com/necessary.js"></script>',
        'enable_analytics' => true,
        'analytics' => '<script>window.analyticsRan = true;</script>',
        'enable_advertising' => true,
        'advertising' => '<script>window.advertisingRan = true;</script>',
    ]);
});

afterEach(function () {
    unset($_COOKIE['AltCookieAddon']);
});

it('outputs the necessary snippet so it runs straight away', function () {
    $output = (new AltCookies)->cookieFields();

    expect($output)->toStartWith('<script src="https://example.com/necessary.js"></script>');
});

it('wraps each optional category in an inert template', function () {
    $output = (new AltCookies)->cookieFields();

    expect($output)
        ->toContain('<template data-alt-cookies-category="analytics"><script>window.analyticsRan = true;</script></template>')
        ->toContain('<template data-alt-cookies-category="advertising"><script>window.advertisingRan = true;</script></template>');
});

it('renders the same page whatever the visitor has consented to', function (string $level) {
    $withoutCookie = (new AltCookies)->cookieFields();

    $_COOKIE['AltCookieAddon'] = $level;

    expect((new AltCookies)->cookieFields())->toBe($withoutCookie);
})->with(['1', '2', '3', '4']);

it('leaves out a category that is switched off', function () {
    $this->writeSettings([
        'enable_analytics' => false,
        'analytics' => '<script>window.analyticsRan = true;</script>',
        'enable_advertising' => true,
        'advertising' => '<script>window.advertisingRan = true;</script>',
    ]);

    $output = (new AltCookies)->cookieFields();

    expect($output)
        ->not->toContain('data-alt-cookies-category="analytics"')
        ->toContain('data-alt-cookies-category="advertising"');
});

it('leaves out a category with nothing in it', function () {
    $this->writeSettings([
        'enable_analytics' => true,
        'analytics' => '',
    ]);

    expect((new AltCookies)->cookieFields())->not->toContain('<template');
});

it('ticks checkboxes from the configured defaults rather than the visitor cookie', function () {
    $this->writeSettings([
        'enable_analytics_default' => true,
        'enable_advertising_default' => false,
    ]);

    $_COOKIE['AltCookieAddon'] = '3';

    $analytics = (new AltCookies)->setContext([])->setParameters(['checkbox' => 'enable_analytics_default'])->checkboxes();
    $advertising = (new AltCookies)->setContext([])->setParameters(['checkbox' => 'enable_advertising_default'])->checkboxes();

    expect($analytics)->toBe('checked')
        ->and($advertising)->toBe('');
});
