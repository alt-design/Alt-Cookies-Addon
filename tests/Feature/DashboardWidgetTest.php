<?php

use AltDesign\AltCookiesAddon\Support\ScanStore;
use AltDesign\AltCookiesAddon\Widgets\CookieScan;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\User;

function storedScan(array $counts = []): array
{
    return [
        'scanned_at' => now()->subHours(3)->toIso8601String(),
        'pages' => [],
        'observed' => [],
        'vendors' => [],
        'counts' => array_merge(
            ['pages' => 12, 'failed' => 0, 'observed' => 4, 'unknown' => 1, 'vendors' => 3],
            $counts
        ),
    ];
}

function renderWidget(): string
{
    return (string) (new CookieScan)->html()?->render();
}

beforeEach(function () {
    $this->user = tap(User::make()->email('widget@example.com')->makeSuper())->save();
});

it('summarises the last scan', function () {
    app(ScanStore::class)->put(storedScan());

    $this->actingAs($this->user);

    expect(renderWidget())
        ->toContain('Cookie Scan')
        ->toContain('cookies observed')
        ->toContain('services recognised')
        ->toContain('3 hours ago')
        ->toContain('across 12 pages')
        ->toContain('View full results');
});

it('prompts for a first scan when nothing has been run', function () {
    $this->actingAs($this->user);

    expect(renderWidget())
        ->toContain('No scan has been run')
        ->toContain('Scan now')
        ->not->toContain('cookies observed');
});

it('flags pages that could not be fetched', function () {
    app(ScanStore::class)->put(storedScan(['failed' => 2]));

    $this->actingAs($this->user);

    expect(renderWidget())->toContain('2 pages could not be fetched');
});

it('shows nothing to a user without the addon permission', function () {
    $viewer = tap(User::make()->email('nowidget@example.com'))->save();

    $this->actingAs($viewer);

    expect((new CookieScan)->html())->toBeNull();
});

it('is registered under a stable handle, since sites name it in their cp config', function () {
    expect(CookieScan::handle())->toBe('alt_cookies_scan');
});

it('returns to the dashboard when the scan was started from the widget', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.scan.run'), ['return' => 'dashboard'])
        ->assertRedirect(cp_route('dashboard'));
});

it('returns to the scan tab when the scan was started there', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.scan.run'))
        ->assertRedirect(cp_route('alt-cookies-addon.index').'#scan');
});
