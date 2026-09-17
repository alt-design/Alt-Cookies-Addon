<?php

use AltDesign\AltCookiesAddon\Support\CookieCatalogue;

beforeEach(function () {
    $this->catalogue = app(CookieCatalogue::class);
});

it('identifies a cookie by exact name', function () {
    $entry = $this->catalogue->identify('_ga');

    expect($entry['provider'])->toBe('Google Analytics')
        ->and($entry['category'])->toBe('analytics')
        ->and($entry['duration'])->toBe('2 years');
});

it('identifies a cookie by wildcard pattern', function () {
    $entry = $this->catalogue->identify('_ga_ABC123XYZ');

    expect($entry['provider'])->toBe('Google Analytics')
        ->and($entry['category'])->toBe('analytics');
});

it('prefers an exact match over a pattern that would also match', function () {
    expect($this->catalogue->identify('_hjSessionUser_12345')['duration'])->toBe('1 year')
        ->and($this->catalogue->identify('_hjAbsoluteSessionInProgress')['duration'])
        ->toBe('Varies, from 30 minutes to 1 year');
});

it('falls back to a case insensitive match', function () {
    expect($this->catalogue->identify('xsrf-token')['provider'])->toBe('Laravel');
});

it('returns null for a cookie it does not know', function () {
    expect($this->catalogue->identify('some_bespoke_cookie'))->toBeNull();
});

it('describes the session cookie this application is actually configured to use', function () {
    $entry = $this->catalogue->identify('test_site_session');

    expect($entry['provider'])->toBe('Test Site')
        ->and($entry['category'])->toBe('necessary')
        ->and($entry['duration'])->toBe('2 hours');
});

it('describes a list of names, marking the ones it does not recognise', function () {
    $described = $this->catalogue->describeAll(['_fbp', 'mystery_cookie']);

    expect($described)->toHaveCount(2)
        ->and($described[0]['provider'])->toBe('Meta Pixel')
        ->and($described[1]['provider'])->toBe('Unknown')
        ->and($described[1]['category'])->toBe('unknown');
});

it('keeps the name it was asked about rather than the pattern that matched', function () {
    expect($this->catalogue->describeAll(['_ga_ABC123'])[0]['name'])->toBe('_ga_ABC123');
});

it('gives every vendor the keys the scanner relies on', function () {
    $this->catalogue->vendors()->each(function (array $vendor) {
        expect($vendor)->toHaveKeys(['name', 'category', 'signatures', 'cookies'])
            ->and($vendor['signatures'])->not->toBeEmpty();
    });
});

it('can describe every cookie a vendor claims to set', function () {
    $this->catalogue->vendors()->each(function (array $vendor) {
        foreach ($vendor['cookies'] as $name) {
            expect($this->catalogue->identify($name))
                ->not->toBeNull("{$vendor['name']} lists {$name}, which is not in the cookie catalogue");
        }
    });
});
