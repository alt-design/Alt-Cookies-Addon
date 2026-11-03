<?php

use AltDesign\AltCookiesAddon\Helpers\Data;
use AltDesign\AltCookiesAddon\Tags\AltCookies;
use Statamic\Facades\User;

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

it('falls back to the default name on installs that have never saved one', function () {
    $this->writeSettings([]);

    expect((new AltCookies)->init())->toContain('window.altCookies.consentEvent = "alt_cookies_consent_update";');
});

it('pushes no event when the name is cleared', function () {
    $this->writeSettings(['consent_event_name' => '']);

    $script = file_get_contents(__DIR__.'/../../resources/js/frontend-manager.js');

    expect((new AltCookies)->init())->toContain('window.altCookies.consentEvent = "";')
        ->and($script)->toContain("if (! window.altCookies.consentEvent) {\n            return;\n        }");
});

it('keeps a cleared name cleared rather than refilling the default', function () {
    $user = tap(User::make()->email('events@example.com')->makeSuper())->save();

    $this->actingAs($user)
        ->post(cp_route('alt-cookies-addon.save'), ['cookie_lifetime' => 30, 'consent_event_name' => ''])
        ->assertOk();

    $data = new Data('settings');
    $shownOnReload = $data->getBlueprint(true)->fields()->addValues($data->all())->preProcess()->values()['consent_event_name'];

    expect($data->get('consent_event_name'))->toBe('')
        ->and($shownOnReload)->toBe('');
});

it('keeps a name with quotes or tags inside its string', function () {
    $name = '"</script><script>alert(1)//';
    $this->writeSettings(['consent_event_name' => $name]);

    $init = (new AltCookies)->init();
    preg_match('/consentEvent = (".*");/', $init, $literal);

    expect($init)->not->toContain('</script><script>')
        ->and(json_decode($literal[1]))->toBe($name);
});
