<?php namespace AltDesign\AltCookiesAddon\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;

/**
 * Class CookieCatalogue
 *
 * Looks up what a cookie is for, and which third party services are recognisable
 * from the markup of a page.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class CookieCatalogue
{
    /**
     * @var Collection<int, array>
     */
    protected Collection $cookies;

    /**
     * @var Collection<int, array>
     */
    protected Collection $vendors;

    public function __construct()
    {
        $this->cookies = collect($this->runtimeCookies())
            ->merge(YAML::parse(file_get_contents(__DIR__.'/../../resources/data/cookies.yaml')))
            ->values();

        $this->vendors = collect(YAML::parse(file_get_contents(__DIR__.'/../../resources/data/vendors.yaml')));
    }

    /**
     * Describe a cookie by name, or return null when it is not one we know.
     *
     * @param  string  $name
     * @return array|null
     */
    public function identify(string $name): ?array
    {
        $exact = $this->cookies->first(
            fn (array $entry) => isset($entry['name']) && $entry['name'] === $name
        );

        if ($exact) {
            return $exact;
        }

        $insensitive = $this->cookies->first(
            fn (array $entry) => isset($entry['name']) && strcasecmp($entry['name'], $name) === 0
        );

        if ($insensitive) {
            return $insensitive;
        }

        return $this->cookies->first(
            fn (array $entry) => isset($entry['pattern']) && Str::is($entry['pattern'], $name)
        );
    }

    /**
     * Every third party service the scanner can recognise.
     *
     * @return Collection<int, array>
     */
    public function vendors(): Collection
    {
        return $this->vendors;
    }

    /**
     * Resolve a vendor's list of cookie names and patterns into described cookies.
     *
     * @param  array  $names
     * @return Collection<int, array>
     */
    public function describeAll(array $names): Collection
    {
        return collect($names)
            ->map(function (string $name) {
                $entry = $this->identify($name);

                return $entry
                    ? array_merge($entry, ['name' => $name])
                    : [
                        'name' => $name,
                        'provider' => 'Unknown',
                        'category' => 'unknown',
                        'duration' => 'Unknown',
                        'purpose' => '',
                    ];
            })
            ->values();
    }

    /**
     * Cookies whose name depends on how this application is configured, so they
     * cannot be shipped as a fixed list.
     *
     * @return array
     */
    protected function runtimeCookies(): array
    {
        $session = config('session.cookie');

        if (! $session) {
            return [];
        }

        return [[
            'name' => $session,
            'provider' => config('app.name') ?: 'This site',
            'category' => 'necessary',
            'duration' => $this->sessionLifetime(),
            'purpose' => 'Identifies the visitor session so state such as form errors, flash messages and control panel login survive between requests. This is the session cookie configured for this application.',
        ]];
    }

    /**
     * @return string
     */
    protected function sessionLifetime(): string
    {
        $minutes = (int) config('session.lifetime', 120);

        if (config('session.expire_on_close')) {
            return 'Session';
        }

        return $minutes >= 60 && $minutes % 60 === 0
            ? ($minutes / 60).' hours'
            : $minutes.' minutes';
    }
}
