<?php

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function scanner(): CookieScanner
{
    return app(CookieScanner::class);
}

function page(array $headers = [], string $body = '<html></html>')
{
    return Http::response($body, 200, $headers);
}

beforeEach(function () {
    $this->site = rtrim(\Statamic\Facades\Site::current()->absoluteUrl(), '/');
});

it('reports a cookie set in a response header', function () {
    Http::fake(['*' => page(['Set-Cookie' => '_ga=GA1.1.123; Max-Age=63072000; path=/; secure'])]);

    $results = scanner()->scan();

    expect($results['observed'])->toHaveCount(1);

    $cookie = $results['observed'][0];

    expect($cookie['name'])->toBe('_ga')
        ->and($cookie['known'])->toBeTrue()
        ->and($cookie['provider'])->toBe('Google Analytics')
        ->and($cookie['category'])->toBe('analytics')
        ->and($cookie['observed_duration'])->toBe('2 years')
        ->and($cookie['flags'])->toBe(['Secure']);
});

it('records a cookie it has never heard of rather than dropping it', function () {
    Http::fake(['*' => page(['Set-Cookie' => 'bespoke_thing=1; path=/'])]);

    $results = scanner()->scan();

    expect($results['observed'][0]['known'])->toBeFalse()
        ->and($results['observed'][0]['category'])->toBe('unknown')
        ->and($results['counts']['unknown'])->toBe(1);
});

it('reads the lifetime from an expires attribute when there is no max-age', function () {
    Http::fake(['*' => page([
        'Set-Cookie' => 'thing=1; expires='.now()->addDays(30)->toRfc7231String().'; path=/',
    ])]);

    expect(scanner()->scan()['observed'][0]['observed_duration'])->toBe('30 days');
});

it('treats a cookie with no lifetime as a session cookie', function () {
    Http::fake(['*' => page(['Set-Cookie' => 'thing=1; path=/; httponly'])]);

    $results = scanner()->scan();

    expect($results['observed'][0]['observed_duration'])->toBe('Session')
        ->and($results['observed'][0]['flags'])->toBe(['HttpOnly']);
});

it('records the security attributes worth seeing', function () {
    Http::fake(['*' => page([
        'Set-Cookie' => 'thing=1; path=/; secure; httponly; samesite=lax',
    ])]);

    expect(scanner()->scan()['observed'][0]['flags'])->toBe(['Secure', 'HttpOnly', 'SameSite=Lax']);
});

it('ignores a malformed set-cookie header', function () {
    Http::fake(['*' => page(['Set-Cookie' => 'nonsense; path=/'])]);

    expect(scanner()->scan()['observed'])->toBeEmpty();
});

it('scans as a visitor who accepted every category', function () {
    Http::fake(['*' => page()]);

    scanner()->scan();

    Http::assertSent(fn (Request $request) => str_contains(
        $request->header('Cookie')[0] ?? '',
        'AltCookieAddon=4'
    ));
});

it('recognises a third party service in page markup', function () {
    Http::fake(['*' => page(
        body: '<html><body><script src="https://connect.facebook.net/en_US/fbevents.js"></script></body></html>'
    )]);

    $vendors = collect(scanner()->scan()['vendors']);

    expect($vendors->pluck('name'))->toContain('Meta Pixel');

    $meta = $vendors->firstWhere('name', 'Meta Pixel');

    expect($meta['category'])->toBe('advertising')
        ->and(collect($meta['cookies'])->pluck('name'))->toContain('_fbp')
        ->and($meta['sources'][0])->toBe($this->site);
});

it('recognises a service configured in the addon settings but never rendered', function () {
    Http::fake(['*' => page()]);

    $this->writeSettings([
        'analytics' => '<script src="https://static.hotjar.com/c/hotjar-123.js"></script>',
    ]);

    $vendors = collect(scanner()->scan()['vendors']);
    $hotjar = $vendors->firstWhere('name', 'Hotjar');

    expect($hotjar)->not->toBeNull()
        ->and($hotjar['sources'])->toBe(['Analytics scripts (Alt Cookies settings)']);
});

it('recognises google analytics from a tag id alone', function () {
    Http::fake(['*' => page()]);

    $this->writeSettings(['enable_google' => true, 'google_tag_id' => 'G-ABC123']);

    expect(collect(scanner()->scan()['vendors'])->pluck('name'))->toContain('Google Analytics 4');
});

it('does not report a service the site does not use', function () {
    Http::fake(['*' => page()]);

    expect(scanner()->scan()['vendors'])->toBeEmpty();
});

it('orders results by consent category, then by name', function () {
    Http::fake(['*' => page(
        body: '<script src="https://snap.licdn.com/li.lms-analytics/insight.min.js"></script>'
            .'<script src="https://js.stripe.com/v3/"></script>'
            .'<script src="https://static.hotjar.com/c/hotjar-1.js"></script>'
            .'<script src="https://connect.facebook.net/en_US/fbevents.js"></script>'
    )]);

    expect(collect(scanner()->scan()['vendors'])->pluck('name')->all())->toBe([
        'Stripe',
        'Hotjar',
        'LinkedIn Insight Tag',
        'Meta Pixel',
    ]);
});

it('reports a page it could not fetch instead of failing the whole scan', function () {
    Http::fake(['*' => Http::response('Nope', 503)]);

    $results = scanner()->scan();

    expect($results['counts']['pages'])->toBe(0)
        ->and($results['counts']['failed'])->toBe(1)
        ->and($results['pages'][0]['ok'])->toBeFalse()
        ->and($results['pages'][0]['status'])->toBe(503);
});

it('always includes the site root', function () {
    expect(scanner()->urls())->toContain(\Statamic\Facades\Site::current()->absoluteUrl());
});

it('stamps the results with when the scan ran', function () {
    Http::fake(['*' => page()]);

    expect(scanner()->scan()['scanned_at'])->toBeString();
});

it('gathers published entries from routable collections', function () {
    config()->set('alt-cookies.scan.per_collection', 2);
    config()->set('alt-cookies.scan.max_pages', 10);

    \Statamic\Facades\Collection::make('pages')->routes('/{slug}')->save();
    \Statamic\Facades\Collection::make('hidden')->save();

    collect(['one', 'two', 'three'])->each(
        fn (string $slug) => \Statamic\Facades\Entry::make()
            ->collection('pages')->slug($slug)->published(true)->save()
    );

    \Statamic\Facades\Entry::make()->collection('pages')->slug('draft')->published(false)->save();
    \Statamic\Facades\Entry::make()->collection('hidden')->slug('nowhere')->published(true)->save();

    $urls = scanner()->urls();

    expect($urls)->toContain(\Statamic\Facades\Site::current()->absoluteUrl())
        ->and(collect($urls)->filter(fn ($url) => str_contains($url, '/one') || str_contains($url, '/two') || str_contains($url, '/three')))
        ->toHaveCount(2)
        ->and(collect($urls)->filter(fn ($url) => str_contains($url, 'draft') || str_contains($url, 'nowhere')))
        ->toBeEmpty();
});

it('caps the total number of pages a scan will request', function () {
    config()->set('alt-cookies.scan.per_collection', 10);
    config()->set('alt-cookies.scan.max_pages', 3);

    \Statamic\Facades\Collection::make('pages')->routes('/{slug}')->save();

    collect(range(1, 8))->each(
        fn (int $i) => \Statamic\Facades\Entry::make()
            ->collection('pages')->slug("page-{$i}")->published(true)->save()
    );

    expect(scanner()->urls())->toHaveCount(3);
});
