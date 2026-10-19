<?php

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\PolicyWriter;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use AltDesign\AltCookiesAddon\Widgets\CookieScan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

function hardeningScan(array $observed = [], array $vendors = []): array
{
    return [
        'scanned_at' => now()->toIso8601String(),
        'pages' => [['url' => 'https://example.com/', 'status' => 200, 'ok' => true, 'error' => null]],
        'observed' => $observed,
        'vendors' => $vendors,
        'counts' => ['pages' => 1, 'failed' => 0, 'observed' => count($observed), 'unknown' => 0, 'vendors' => count($vendors)],
    ];
}

function hardeningCookie(string $name, string $source = 'header'): array
{
    return [
        'name' => $name,
        'source' => $source,
        'known' => false,
        'provider' => 'Unknown',
        'category' => 'unknown',
        'duration' => 'Unknown',
        'purpose' => '',
        'observed_duration' => 'Session',
        'flags' => [],
        'urls' => ['https://example.com/'],
    ];
}

beforeEach(function () {
    $this->user = tap(User::make()->email('hardening@example.com')->makeSuper())->save();
});

it('does not request an entry that redirects elsewhere', function () {
    Http::fake(['*' => Http::response('<html></html>', 200)]);

    Collection::make('pages')->routes('/{slug}')->save();

    Entry::make()->collection('pages')->slug('moved')->published(true)
        ->data(['redirect' => 'https://other.example/landing'])->save();
    Entry::make()->collection('pages')->slug('moved-locally')->published(true)
        ->data(['redirect' => '/somewhere-else'])->save();
    Entry::make()->collection('pages')->slug('stays')->published(true)->save();

    $urls = app(CookieScanner::class)->urls();

    expect($urls)->not->toContain('https://other.example/landing')
        ->and(collect($urls)->filter(fn (string $url) => str_contains($url, '/moved') || str_contains($url, '/somewhere-else')))->toBeEmpty()
        ->and(collect($urls)->filter(fn (string $url) => str_contains($url, '/stays')))->toHaveCount(1);

    app(CookieScanner::class)->scan();

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'other.example'));
});

it('filters out any url on a different host to the site', function () {
    Collection::make('pages')->routes('/{slug}')->save();
    Entry::make()->collection('pages')->slug('about')->published(true)->save();

    $scanner = new class(app(\AltDesign\AltCookiesAddon\Support\CookieCatalogue::class)) extends CookieScanner {
        protected function hostOf(string $url): string
        {
            return str_contains($url, '/about') ? 'elsewhere.example' : parent::hostOf($url);
        }
    };

    expect($scanner->urls())->toBe([Site::current()->absoluteUrl()]);
});

it('does not follow a redirect to another host and says why', function () {
    Http::fake(['*' => Http::response('', 302, [
        'Location' => 'https://other.example/landing',
        'Set-Cookie' => 'sneaky=1; path=/',
    ])]);

    $results = app(CookieScanner::class)->scan();

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'other.example'));

    expect($results['counts']['pages'])->toBe(0)
        ->and($results['counts']['failed'])->toBe(1)
        ->and($results['pages'][0]['ok'])->toBeFalse()
        ->and($results['pages'][0]['error'])->toBe('Redirected to another site');
});

it('still follows a redirect within the same host', function () {
    $root = rtrim(Site::current()->absoluteUrl(), '/');

    Http::fake([
        $root => Http::response('', 302, ['Location' => $root.'/en']),
        $root.'/en' => Http::response('<html></html>', 200, ['Set-Cookie' => 'locale=en; path=/']),
        '*' => Http::response('', 404),
    ]);

    $results = app(CookieScanner::class)->scan();

    expect($results['counts']['pages'])->toBe(1)
        ->and($results['observed'][0]['name'])->toBe('locale');
});

it('keeps exception text out of the stored results and the report', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 7: connect to db.internal.example:3306 refused'));

    $results = app(CookieScanner::class)->scan();

    app(ScanStore::class)->put($results);

    expect($results['pages'][0]['error'])->toBe('Could not connect')
        ->and(File::get(app(ScanStore::class)->path()))->not->toContain('db.internal.example');

    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.index'))
        ->assertOk()
        ->assertSee('Could not connect', false)
        ->assertDontSee('db.internal.example', false);
});

it('reports a timeout as a timeout', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds'));

    expect(app(CookieScanner::class)->scan()['pages'][0]['error'])->toBe('Timed out');
});

it('refuses each scan route to a control panel user without the addon permission', function (string $route) {
    $role = Role::make('limited')->title('Limited')->permissions(['access cp']);
    $role->save();

    $editor = tap(User::make()->email('limited@example.com')->assignRole($role))->save();

    $this->actingAs($editor)
        ->postJson(cp_route($route))
        ->assertForbidden();
})->with(['alt-cookies-addon.scan.run', 'alt-cookies-addon.scan.clear', 'alt-cookies-addon.scan.observed']);

it('renders markup in a cookie name and vendor name as text in the scan tab', function () {
    $results = hardeningScan(
        [hardeningCookie('<script>alert("cookie")</script>')],
        [[
            'name' => '<img src=x onerror=alert("vendor")>',
            'category' => 'analytics',
            'note' => null,
            'sources' => ['https://example.com/'],
            'cookies' => [['name' => '<b>nested</b>', 'provider' => '<u>prov</u>', 'duration' => '1 year', 'purpose' => '<i>purpose</i>']],
        ]]
    );

    $html = view('alt-cookies::scan-panel', [
        'results' => $results,
        'policyMarkdown' => app(PolicyWriter::class)->markdown($results),
        'policyHtml' => app(PolicyWriter::class)->html($results),
    ])->render();

    expect($html)
        ->toContain('&lt;script&gt;alert(&quot;cookie&quot;)&lt;/script&gt;')
        ->toContain('&lt;img src=x onerror=alert(&quot;vendor&quot;)&gt;')
        ->not->toContain('<script>alert')
        ->not->toContain('<img src=x')
        ->not->toContain('<b>nested</b>')
        ->not->toContain('<i>purpose</i>')
        ->not->toContain('<u>prov</u>');
});

it('keeps stored cookie and vendor names out of the widget markup', function () {
    $this->actingAs($this->user);

    app(ScanStore::class)->put(hardeningScan(
        [hardeningCookie('<script>alert("cookie")</script>')],
        [[
            'name' => '<img src=x onerror=alert("vendor")>',
            'category' => 'analytics',
            'note' => null,
            'sources' => [],
            'cookies' => [],
        ]]
    ));

    expect((string) (new CookieScan)->html()->render())
        ->not->toContain('<script>alert')
        ->not->toContain('<img src=x');
});

it('shows where each observed cookie was seen', function () {
    app(ScanStore::class)->put(hardeningScan([
        hardeningCookie('from_header', 'header'),
        hardeningCookie('from_browser', 'browser'),
        hardeningCookie('from_both', 'both'),
    ]));

    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.index'))
        ->assertOk()
        ->assertSee('Seen in a response header', false)
        ->assertSee('Seen in the browser', false)
        ->assertSee('Seen in a header and in the browser', false);
});

it('warns next to the scan button that the pages are loaded with every category accepted', function () {
    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.index'))
        ->assertOk()
        ->assertSee('with every cookie category accepted', false);

});

it('leaves the browser warning off when the browser pass is turned off', function () {
    config()->set('alt-cookies.scan.run_in_browser', false);

    $this->actingAs($this->user)
        ->get(cp_route('alt-cookies-addon.index'))
        ->assertOk()
        ->assertDontSee('with every cookie category accepted', false);
});

it('labels the widget figures as the server check and points to the scan tab', function () {
    $this->actingAs($this->user);

    app(ScanStore::class)->put(hardeningScan());

    expect((string) (new CookieScan)->html()->render())
        ->toContain('server check only')
        ->toContain('Scan tab');
});

it('throttles the scan and observed routes', function () {
    $routes = app('router')->getRoutes();

    foreach (['alt-cookies-addon.scan.run', 'alt-cookies-addon.scan.observed'] as $name) {
        expect($routes->getByName('statamic.cp.'.$name)->gatherMiddleware())->toContain('throttle:6,1');
    }
});
