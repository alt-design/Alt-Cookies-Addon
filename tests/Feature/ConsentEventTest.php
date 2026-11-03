<?php

use AltDesign\AltCookiesAddon\Tags\AltCookies;

it('announces a choice to Tag Manager after updating consent', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js');

    expect($script)
        ->toContain("window.altCookies.updateConsent();\n        window.altCookies.announceConsent();")
        ->toContain("'event': window.altCookies.consentEvent");
});

it('names the event from the settings', function () {
    $this->writeSettings(['consent_event_name' => 'alt_cookies_accepted']);

    expect((new AltCookies)->init())->toContain('window.altCookies.consentEvent = "alt_cookies_accepted";');
});

it('falls back to the default name when none is set', function (array $settings) {
    $this->writeSettings($settings);

    expect((new AltCookies)->init())->toContain('window.altCookies.consentEvent = "alt_cookies_consent_update";');
})->with([
    'missing' => [[]],
    'blank' => [['consent_event_name' => '']],
]);

it('keeps a name with quotes or tags inside its string', function () {
    $name = '"</script><script>alert(1)//';
    $this->writeSettings(['consent_event_name' => $name]);

    $init = (new AltCookies)->init();
    preg_match('/consentEvent = (".*");/', $init, $literal);

    expect($init)->not->toContain('</script><script>')
        ->and(json_decode($literal[1]))->toBe($name);
});
