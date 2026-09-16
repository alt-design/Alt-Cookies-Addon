<?php

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\User;

beforeEach(function () {
    $this->user = tap(User::make()->email('scanner@example.com')->makeSuper())->save();
});

it('shows an empty state before anything has been scanned', function () {
    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.scan.index'))
        ->assertOk()
        ->assertSee('No scan has been run yet.');
});

it('runs a scan, stores it and redirects back', function () {
    Http::fake(['*' => Http::response('<html></html>', 200, [
        'Set-Cookie' => '_ga=GA1.1.1; Max-Age=63072000; path=/',
    ])]);

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.scan.run'))
        ->assertRedirect(cp_route('alt-cookies-addon.scan.index'))
        ->assertSessionHas('success');

    expect(app(ScanStore::class)->get()['observed'][0]['name'])->toBe('_ga');
});

it('shows the stored results when the page is reopened', function () {
    Http::fake(['*' => Http::response('<html></html>', 200, [
        'Set-Cookie' => '_ga=GA1.1.1; Max-Age=63072000; path=/',
    ])]);

    $this->actingAs($this->user)->post(cp_route('alt-cookies-addon.scan.run'));

    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.scan.index'))
        ->assertOk()
        ->assertSee('_ga')
        ->assertSee('Google Analytics')
        ->assertDontSee('No scan has been run yet.');
});

it('clears stored results', function () {
    app(ScanStore::class)->put(['scanned_at' => now()->toIso8601String(), 'pages' => [], 'observed' => [], 'vendors' => [], 'counts' => []]);

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.scan.clear'))
        ->assertRedirect(cp_route('alt-cookies-addon.scan.index'));

    expect(app(ScanStore::class)->get())->toBeNull();
});

it('keeps the page behind the addon permission', function () {
    $this->get(cp_route('alt-cookies-addon.scan.index'))->assertRedirect();

    $viewer = tap(User::make()->email('viewer@example.com'))->save();

    $this->actingAs($viewer)
        ->get(cp_route('alt-cookies-addon.scan.index'))
        ->assertRedirect();
});

it('reports a scan that could not run rather than throwing', function () {
    $this->mock(CookieScanner::class)
        ->shouldReceive('scan')
        ->andThrow(new RuntimeException('DNS is having a day'));

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.scan.run'))
        ->assertRedirect(cp_route('alt-cookies-addon.scan.index'))
        ->assertSessionHas('error');

    expect(app(ScanStore::class)->get())->toBeNull();
});
