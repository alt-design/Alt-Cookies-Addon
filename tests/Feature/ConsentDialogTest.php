<?php

use AltDesign\AltCookiesAddon\Tags\AltCookies;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::copyDirectory(__DIR__.'/../../resources/dist/build', public_path('vendor/alt-cookies/build'));
    $this->writeSettings(['enable_analytics' => true, 'enable_advertising' => true]);
});

afterEach(function () {
    File::deleteDirectory(public_path('vendor/alt-cookies'));
});

function renderToast(array $parameters = []): string
{
    return (new AltCookies)->setContext([])->setParameters($parameters)->toast()->render();
}

it('marks the overlay popup as a modal dialog named by its title', function () {
    expect(renderToast())
        ->toContain('role="dialog" aria-modal="true" aria-labelledby="alt-cookies-consent-toast-title" aria-describedby="alt-cookies-consent-toast-text"')
        ->toContain('id="alt-cookies-consent-toast-title"')
        ->toContain('id="alt-cookies-consent-toast-text"');
});

it('marks the simple overlay popup as a modal dialog too', function () {
    $this->writeSettings(['simple_popup' => true]);

    expect(renderToast())
        ->toContain('role="dialog" aria-modal="true" aria-labelledby="alt-cookies-consent-toast-title"')
        ->toContain('Accept All Cookies');
});

it('keeps the banner without the overlay as a non-modal region', function () {
    expect(renderToast(['overlay' => false]))
        ->toContain('role="region" aria-label="Cookie consent"')
        ->not->toContain('role="dialog"')
        ->not->toContain('aria-modal');
});

it('opens and closes the popup through the dialog handling in the front-end script', function () {
    $init = (new AltCookies)->init();
    $script = file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js');

    expect($init)
        ->toContain('window.altCookies.showToast()')
        ->toContain('window.altCookies.showPreferences(trigger)')
        ->not->toContain("classList.remove('alt-cookies-hidden')");

    expect($script)
        ->toContain('window.altCookies.showToast(trigger)')
        ->toContain("getAttribute('aria-modal') === 'true'");
});
