<?php

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\User;

beforeEach(function () {
    $this->user = tap(User::make()->email('scanner@example.com')->makeSuper())->save();
});

function settingsPage()
{
    return test()->actingAs(test()->user)->get(cp_route('alt-cookies-addon.index'));
}

function runScan(array $headers = ['Set-Cookie' => '_ga=GA1.1.1; Max-Age=63072000; path=/'])
{
    Http::fake(['*' => Http::response('<html></html>', 200, $headers)]);

    return test()->actingAs(test()->user)->post(cp_route('alt-cookies-addon.scan.run'));
}

it('puts the scan in a tab on the settings page', function () {
    settingsPage()
        ->assertOk()
        ->assertSee('scan_report')
        ->assertSee('No scan has been run yet.', false);
});

it('shows the results in that tab once a scan has run', function () {
    runScan();

    settingsPage()
        ->assertOk()
        ->assertSee('_ga', false)
        ->assertSee('Google Analytics', false)
        ->assertDontSee('No scan has been run yet.', false);
});

it('offers the scan as cookie policy copy in the same tab', function () {
    runScan();

    settingsPage()
        ->assertOk()
        ->assertSee('Copy as Markdown', false)
        ->assertSee('Copy as HTML', false)
        ->assertSee('Cookies we use', false);
});

it('does not save the scan report back into the settings file', function () {
    runScan();

    $this->actingAs($this->user)
        ->post(cp_route('alt-cookies-addon.save'), ['cookie_lifetime' => 30, 'scan_report' => 'nonsense'])
        ->assertOk();

    expect((new \AltDesign\AltCookiesAddon\Helpers\Data('settings'))->all())
        ->not->toHaveKey('scan_report');
});

it('stores the results when a scan is run', function () {
    runScan()->assertRedirect(cp_route('alt-cookies-addon.index').'#scan');

    expect(app(ScanStore::class)->get()['observed'][0]['name'])->toBe('_ga');
});

it('answers with json when the tab asks over fetch', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.run'))
        ->assertOk()
        ->assertJsonStructure(['counts' => ['pages', 'observed', 'vendors']]);
});

it('answers with json when a scan fails over fetch', function () {
    $this->mock(CookieScanner::class)
        ->shouldReceive('scan')
        ->andThrow(new RuntimeException('DNS is having a day'));

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.run'))
        ->assertStatus(500)
        ->assertJsonPath('message', 'The scan could not be completed: DNS is having a day');

    expect(app(ScanStore::class)->get())->toBeNull();
});

it('clears stored results', function () {
    app(ScanStore::class)->put(['scanned_at' => now()->toIso8601String(), 'pages' => [], 'observed' => [], 'vendors' => [], 'counts' => []]);

    $this->actingAs($this->user)
        ->postJson(cp_route('alt-cookies-addon.scan.clear'))
        ->assertOk();

    expect(app(ScanStore::class)->get())->toBeNull();
});

it('sends the old scan page to the tab that replaced it', function () {
    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.scan.index'))
        ->assertRedirect(cp_route('alt-cookies-addon.index').'#scan');
});

it('keeps the scan behind the addon permission', function () {
    $this->post(cp_route('alt-cookies-addon.scan.run'))->assertRedirect();

    $viewer = tap(User::make()->email('viewer@example.com'))->save();

    $this->actingAs($viewer)
        ->post(cp_route('alt-cookies-addon.scan.run'))
        ->assertRedirect();
});
