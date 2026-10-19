<?php

use AltDesign\AltCookiesAddon\Tags\AltCookies;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::copyDirectory(__DIR__.'/../../resources/dist/build', public_path('vendor/alt-cookies/build'));
});

afterEach(function () {
    File::deleteDirectory(public_path('vendor/alt-cookies'));
});

function consentPanel(): string
{
    return view('alt-cookies::consent')->render();
}

it('asks only about the categories switched on in the settings', function () {
    $this->writeSettings(['enable_analytics' => true, 'enable_advertising' => false]);

    expect(consentPanel())
        ->toContain('id="alt-cookies-analytics"')
        ->not->toContain('id="alt-cookies-advertising"');
});

it('asks about both categories when both are switched on', function () {
    $this->writeSettings(['enable_analytics' => true, 'enable_advertising' => true]);

    expect(consentPanel())
        ->toContain('id="alt-cookies-analytics"')
        ->toContain('id="alt-cookies-advertising"');
});

it('only answers for the categories it knows', function () {
    $this->writeSettings(['enable_analytics' => true, 'enable_necessary' => true]);

    $tag = (new AltCookies)->setContext([])->setParameters(['category' => 'analytics']);
    expect($tag->enabled())->toBeTrue();

    $tag = (new AltCookies)->setContext([])->setParameters(['category' => 'necessary']);
    expect($tag->enabled())->toBeFalse();
});

it('counts a missing checkbox as declined in the front-end script', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js');

    expect($script)
        ->toContain('document.getElementById("alt-cookies-analytics")?.checked')
        ->toContain('document.getElementById("alt-cookies-advertising")?.checked');
});
