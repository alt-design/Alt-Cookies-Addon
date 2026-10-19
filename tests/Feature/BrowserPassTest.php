<?php

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\User;

beforeEach(function () {
    $this->user = tap(User::make()->email('deep@example.com')->makeSuper())->save();
    $this->scanner = app(CookieScanner::class);
});

function headerScan(): array
{
    Http::fake(['*' => Http::response('<html></html>', 200, [
        'Set-Cookie' => 'XSRF-TOKEN=abc; Max-Age=7200; path=/; secure',
    ])]);

    return app(CookieScanner::class)->scan();
}

it('marks cookies seen in a response header as such', function () {
    expect(headerScan()['observed'][0]['source'])->toBe('header');
});

it('describes a cookie a browser saw and the header scan missed', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [
        ['name' => '_ga', 'url' => 'https://example.com/'],
    ]);

    $ga = collect($merged['observed'])->firstWhere('name', '_ga');

    expect($ga['source'])->toBe('browser')
        ->and($ga['provider'])->toBe('Google Analytics')
        ->and($ga['category'])->toBe('analytics')
        ->and($ga['known'])->toBeTrue()
        ->and($ga['urls'])->toBe(['https://example.com/']);
});

it('says a browser cannot see a lifetime rather than inventing one', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [['name' => '_ga']]);

    expect(collect($merged['observed'])->firstWhere('name', '_ga')['observed_duration'])
        ->toBe('Not visible to a browser scan')
        ->and(collect($merged['observed'])->firstWhere('name', '_ga')['flags'])->toBe([]);
});

it('records a cookie seen both ways without listing it twice', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [
        ['name' => 'XSRF-TOKEN', 'url' => 'https://example.com/other'],
    ]);

    $matches = collect($merged['observed'])->where('name', 'XSRF-TOKEN');

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['source'])->toBe('both')
        ->and($matches->first()['flags'])->toBe(['Secure']);
});

it('keeps an unrecognised cookie rather than dropping it', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [['name' => 'mystery_thing']]);

    $found = collect($merged['observed'])->firstWhere('name', 'mystery_thing');

    expect($found['known'])->toBeFalse()
        ->and($found['category'])->toBe('unknown')
        ->and($merged['counts']['unknown'])->toBe(1);
});

it('counts what the browser contributed', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [
        ['name' => '_ga'],
        ['name' => '_fbp'],
        ['name' => 'XSRF-TOKEN'],
    ]);

    expect($merged['counts']['observed'])->toBe(3)
        ->and($merged['counts']['browser'])->toBe(3)
        ->and($merged['browser_scanned_at'])->toBeString();
});

it('keeps results ordered by category then name after merging', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [
        ['name' => '_fbp'],
        ['name' => '_ga'],
        ['name' => '__stripe_mid'],
    ]);

    expect(collect($merged['observed'])->pluck('name')->all())
        ->toBe(['__stripe_mid', 'XSRF-TOKEN', '_ga', '_fbp']);
});

it('records what a deep scan reports', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $url = app(ScanStore::class)->get()['pages'][0]['url'];

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), [
            'cookies' => [['name' => '_hjSession_123', 'url' => $url]],
        ])
        ->assertOk()
        ->assertJsonPath('counts.browser', 1);

    expect(collect(app(ScanStore::class)->get()['observed'])->pluck('name'))
        ->toContain('_hjSession_123');
});

it('refuses observations when there is no scan to add them to', function () {
    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), ['cookies' => [['name' => '_ga']]])
        ->assertStatus(409);
});

it('rejects a cookie name that could not be a cookie name', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);
    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), [
            'cookies' => [['name' => 'has spaces; and=separators']],
        ])
        ->assertStatus(422);
});

it('rejects a url that was not part of the scan', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);
    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), [
            'cookies' => [['name' => '_ga', 'url' => 'https://somewhere-else.example/']],
        ])
        ->assertStatus(422);
});

it('keeps the endpoint behind the addon permission', function () {
    $viewer = tap(User::make()->email('deepviewer@example.com'))->save();

    $this->actingAs($viewer)
        ->post(cp_route('alt-cookies-addon.scan.observed'), ['cookies' => []])
        ->assertRedirect();
});

it('tells the control panel which pages to load and whether to bother', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.run'))
        ->assertOk()
        ->assertJsonPath('runInBrowser', true)
        ->assertJsonStructure(['pages', 'counts', 'runInBrowser']);
});

it('says not to bother when the browser pass is turned off', function () {
    config()->set('alt-cookies.scan.run_in_browser', false);

    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.run'))
        ->assertOk()
        ->assertJsonPath('runInBrowser', false);
});

it('only offers pages the header pass could actually fetch', function () {
    Http::fake(['*' => Http::response('Nope', 503)]);

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.run'))
        ->assertOk()
        ->assertJsonPath('pages', []);
});

it('records a page the browser was refused', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $url = app(ScanStore::class)->get()['pages'][0]['url'];

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), [
            'cookies' => [],
            'blocked' => [$url],
        ])
        ->assertOk()
        ->assertJsonPath('counts.blocked', 1);

    expect(app(ScanStore::class)->get()['browser_blocked'])->toBe([$url]);
});

it('will not take a blocked page that was not in the scan', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.observed'), [
            'cookies' => [],
            'blocked' => ['https://somewhere-else.example/'],
        ])
        ->assertStatus(422);
});

it('reports no blocked pages when every page loaded', function () {
    $merged = $this->scanner->mergeBrowserObservations(headerScan(), [['name' => '_ga']]);

    expect($merged['browser_blocked'])->toBe([])
        ->and($merged['counts']['blocked'])->toBe(0);
});
