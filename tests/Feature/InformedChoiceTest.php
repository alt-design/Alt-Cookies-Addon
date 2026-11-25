<?php

use AltDesign\AltCookiesAddon\Support\PolicyWriter;
use AltDesign\AltCookiesAddon\Tags\AltCookies;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::copyDirectory(__DIR__.'/../../resources/dist/build', public_path('vendor/alt-cookies/build'));
});

afterEach(function () {
    File::deleteDirectory(public_path('vendor/alt-cookies'));
});

function bannerWith(array $settings): string
{
    test()->writeSettings($settings);

    return (new AltCookies)->setContext([])->setParameters([])->toast()->render();
}

dataset('banners', [
    'checkbox banner' => [false],
    'simple banner' => [true],
]);

it('explains what each category it asks about is for', function (bool $simple) {
    expect(bannerWith(['simple_popup' => $simple, 'enable_analytics' => true, 'enable_advertising' => false]))
        ->toContain('What each type of cookie is for')
        ->toContain(e(PolicyWriter::BLURBS['necessary']))
        ->toContain(e(PolicyWriter::BLURBS['analytics']))
        ->not->toContain(e(PolicyWriter::BLURBS['advertising']));
})->with('banners');

it('uses the site\'s own wording for a category, escaped', function () {
    expect(bannerWith(['enable_analytics' => true, 'analytics_purpose' => 'Counts visits <b>only</b>']))
        ->toContain('Counts visits &lt;b&gt;only&lt;/b&gt;')
        ->not->toContain(e(PolicyWriter::BLURBS['analytics']));
});

it('links to the cookie policy when one is set', function (bool $simple) {
    expect(bannerWith(['simple_popup' => $simple, 'policy_url' => '/cookie-policy']))
        ->toContain('<a href="/cookie-policy"');
})->with('banners');

it('leaves the policy link out when none is set, or when it is not a web address', function (?string $url) {
    expect(bannerWith(['policy_url' => $url]))->not->toContain('Read our cookie policy');
})->with([null, '', 'javascript:alert(1)', '//other.example/cookies']);

it('tells the page where the policy is so the banner can stay closed there', function () {
    test()->writeSettings(['policy_url' => '/cookie-policy']);
    expect((new AltCookies)->init())->toContain('const policyUrl = "\/cookie-policy";');

    test()->writeSettings([]);
    expect((new AltCookies)->init())->toContain('const policyUrl = null;');
});

it('labels the decline button from the settings, escaped', function (bool $simple) {
    expect(bannerWith(['simple_popup' => $simple, 'decline_label' => 'Reject <All>']))
        ->toContain('>Reject &lt;All&gt;</button>')
        ->not->toContain('Accept Necessary');
})->with('banners');

it('keeps the original decline label when none is set', function (?string $label) {
    expect(bannerWith(['decline_label' => $label]))->toContain('>Accept Necessary</button>');
})->with([null, '']);

it('lets keyboard users reach the explanation inside the dialog', function () {
    expect(file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js'))
        ->toContain("'a[href], summary, button:not([disabled])");
});
