<?php

use AltDesign\AltCookiesAddon\Support\PolicyWriter;

beforeEach(function () {
    $this->writer = app(PolicyWriter::class);
});

function scanResults(array $observed = [], array $vendors = []): array
{
    return [
        'scanned_at' => now()->toIso8601String(),
        'pages' => [],
        'observed' => $observed,
        'vendors' => $vendors,
        'counts' => ['pages' => 1, 'failed' => 0, 'observed' => count($observed), 'unknown' => 0, 'vendors' => count($vendors)],
    ];
}

function observedCookie(array $overrides = []): array
{
    return array_merge([
        'name' => 'XSRF-TOKEN',
        'known' => true,
        'provider' => 'Laravel',
        'category' => 'necessary',
        'duration' => '2 hours',
        'purpose' => 'Cross-site request forgery token.',
        'observed_duration' => '2 hours',
        'flags' => [],
        'urls' => ['https://example.com'],
    ], $overrides);
}

function vendorResult(string $name, string $category, array $cookies): array
{
    return ['name' => $name, 'category' => $category, 'note' => null, 'sources' => ['https://example.com'], 'cookies' => $cookies];
}

it('groups observed cookies by their own category', function () {
    $grouped = $this->writer->grouped(scanResults([observedCookie()]));

    expect($grouped->keys()->all())->toBe(['necessary'])
        ->and($grouped['necessary'][0]['name'])->toBe('XSRF-TOKEN');
});

it('files a vendor cookie under the vendor category, not the cookie category', function () {
    $results = scanResults([], [
        vendorResult('LinkedIn Insight Tag', 'advertising', [
            ['name' => 'bscookie', 'provider' => 'LinkedIn', 'category' => 'necessary', 'duration' => '1 year', 'purpose' => 'Secure browser identifier.'],
        ]),
    ]);

    $grouped = $this->writer->grouped($results);

    expect($grouped->keys()->all())->toBe(['advertising'])
        ->and($grouped['advertising'][0]['name'])->toBe('bscookie');
});

it('orders categories from necessary through to unrecognised', function () {
    $results = scanResults(
        [observedCookie(['name' => 'mystery', 'known' => false, 'category' => 'unknown', 'provider' => 'Unknown', 'purpose' => ''])],
        [
            vendorResult('Meta Pixel', 'advertising', [['name' => '_fbp', 'provider' => 'Meta Pixel', 'category' => 'advertising', 'duration' => '90 days', 'purpose' => 'x']]),
            vendorResult('Stripe', 'necessary', [['name' => '__stripe_mid', 'provider' => 'Stripe', 'category' => 'necessary', 'duration' => '1 year', 'purpose' => 'x']]),
            vendorResult('Hotjar', 'analytics', [['name' => '_hjSession_1', 'provider' => 'Hotjar', 'category' => 'analytics', 'duration' => '30 minutes', 'purpose' => 'x']]),
            vendorResult('Google Maps', 'functional', [['name' => 'NID', 'provider' => 'Google', 'category' => 'advertising', 'duration' => '6 months', 'purpose' => 'x']]),
        ]
    );

    expect($this->writer->grouped($results)->keys()->all())
        ->toBe(['necessary', 'functional', 'analytics', 'advertising', 'unknown']);
});

it('lists a cookie once even when two services set it', function () {
    $results = scanResults([], [
        vendorResult('YouTube', 'advertising', [['name' => 'CONSENT', 'provider' => 'Google', 'category' => 'necessary', 'duration' => '2 years', 'purpose' => 'x']]),
        vendorResult('Google Maps', 'functional', [['name' => 'CONSENT', 'provider' => 'Google', 'category' => 'necessary', 'duration' => '2 years', 'purpose' => 'x']]),
    ]);

    expect($this->writer->grouped($results)->flatten(1)->pluck('name')->all())->toBe(['CONSENT']);
});

it('sorts cookies within a category by name, case insensitively', function () {
    $results = scanResults([
        observedCookie(['name' => 'zeta']),
        observedCookie(['name' => 'Alpha']),
        observedCookie(['name' => 'beta']),
    ]);

    expect($this->writer->grouped($results)['necessary']->pluck('name')->all())
        ->toBe(['Alpha', 'beta', 'zeta']);
});

it('falls back to the observed lifetime when the cookie is not in the catalogue', function () {
    $results = scanResults([observedCookie([
        'name' => 'mystery', 'known' => false, 'duration' => 'Unknown', 'observed_duration' => '45 minutes',
    ])]);

    expect($this->writer->grouped($results)['necessary'][0]['duration'])->toBe('45 minutes');
});

it('writes markdown with a heading, a blurb and a row per cookie', function () {
    $markdown = $this->writer->markdown(scanResults([observedCookie()]));

    expect($markdown)
        ->toContain('## Cookies we use')
        ->toContain('### Strictly necessary cookies')
        ->toContain(PolicyWriter::BLURBS['necessary'])
        ->toContain('| Cookie | Provider | Lasts | Purpose |')
        ->toContain('| `XSRF-TOKEN` | Laravel | 2 hours | Cross-site request forgery token. |');
});

it('marks a cookie it could not describe rather than leaving the cell empty', function () {
    $results = scanResults([observedCookie(['name' => 'mystery', 'known' => false, 'purpose' => '', 'provider' => 'Unknown', 'category' => 'unknown'])]);

    expect($this->writer->markdown($results))->toContain('Describe this cookie before publishing.');
});

it('writes html with escaped values', function () {
    $results = scanResults([observedCookie(['name' => 'a<b>c', 'purpose' => 'Tom & Jerry'])]);

    $html = $this->writer->html($results);

    expect($html)
        ->toContain('<h2>Cookies we use</h2>')
        ->toContain('a&lt;b&gt;c')
        ->toContain('Tom &amp; Jerry')
        ->not->toContain('<b>c');
});

it('produces just the preamble when a scan found nothing', function () {
    $markdown = $this->writer->markdown(scanResults());

    expect($markdown)
        ->toContain('## Cookies we use')
        ->not->toContain('###');
});
