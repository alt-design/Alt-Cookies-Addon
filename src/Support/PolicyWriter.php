<?php namespace AltDesign\AltCookiesAddon\Support;

use Illuminate\Support\Collection;

/**
 * Class PolicyWriter
 *
 * Turns a scan into cookie policy copy an editor can paste into their own page.
 * Observed and likely cookies are merged, because a policy describes what may be
 * set rather than what a single request happened to see.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class PolicyWriter
{
    public const HEADINGS = [
        'necessary' => 'Strictly necessary cookies',
        'functional' => 'Functional cookies',
        'analytics' => 'Analytics cookies',
        'advertising' => 'Advertising cookies',
        'unknown' => 'Cookies still to be described',
    ];

    public const BLURBS = [
        'necessary' => 'These cookies are needed for the site to work. They keep you signed in, remember what you put in a form and protect against fraudulent requests. They cannot be turned off.',
        'functional' => 'These cookies remember choices you make, such as the settings on an embedded video player. The site works without them, but some things will not remember what you picked.',
        'analytics' => 'These cookies help us understand how people use the site, so we can improve it. They are only set if you accept analytics cookies.',
        'advertising' => 'These cookies are used to measure advertising and to show you adverts that are more relevant to you, including on other websites. They are only set if you accept advertising cookies.',
        'unknown' => 'These were found on the site but are not in the addon catalogue. Describe each one, or remove whatever sets it, before publishing this policy.',
    ];

    /**
     * @param  array  $results
     * @return string
     */
    public function markdown(array $results): string
    {
        $out = ['## Cookies we use', '', $this->intro(), ''];

        foreach ($this->grouped($results) as $category => $cookies) {
            $out[] = '### '.self::HEADINGS[$category];
            $out[] = '';
            $out[] = self::BLURBS[$category];
            $out[] = '';
            $out[] = '| Cookie | Provider | Lasts | Purpose |';
            $out[] = '| --- | --- | --- | --- |';

            foreach ($cookies as $cookie) {
                $out[] = sprintf(
                    '| `%s` | %s | %s | %s |',
                    $cookie['name'],
                    $cookie['provider'],
                    $cookie['duration'],
                    $this->purpose($cookie)
                );
            }

            $out[] = '';
        }

        return trim(implode("\n", $out))."\n";
    }

    /**
     * @param  array  $results
     * @return string
     */
    public function html(array $results): string
    {
        $out = ['<h2>Cookies we use</h2>', '<p>'.e($this->intro()).'</p>'];

        foreach ($this->grouped($results) as $category => $cookies) {
            $out[] = '<h3>'.e(self::HEADINGS[$category]).'</h3>';
            $out[] = '<p>'.e(self::BLURBS[$category]).'</p>';
            $out[] = '<table>';
            $out[] = '    <thead>';
            $out[] = '        <tr><th>Cookie</th><th>Provider</th><th>Lasts</th><th>Purpose</th></tr>';
            $out[] = '    </thead>';
            $out[] = '    <tbody>';

            foreach ($cookies as $cookie) {
                $out[] = sprintf(
                    '        <tr><td><code>%s</code></td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    e($cookie['name']),
                    e($cookie['provider']),
                    e($cookie['duration']),
                    e($this->purpose($cookie))
                );
            }

            $out[] = '    </tbody>';
            $out[] = '</table>';
        }

        return implode("\n", $out)."\n";
    }

    /**
     * Every cookie the scan knows about, by consent category, deduplicated by name.
     *
     * @param  array  $results
     * @return Collection<string, Collection<int, array>>
     */
    public function grouped(array $results): Collection
    {
        $observed = collect($results['observed'] ?? [])->map(fn (array $cookie) => [
            'name' => $cookie['name'],
            'provider' => $cookie['provider'],
            'duration' => $cookie['known'] ? $cookie['duration'] : $cookie['observed_duration'],
            'purpose' => $cookie['purpose'],
            'category' => $cookie['category'],
        ]);

        // A cookie reached through a vendor takes the vendor's category, not its own.
        // LinkedIn calls bscookie necessary because LinkedIn needs it; on a site that
        // only loads LinkedIn once advertising consent is given, it is an advertising
        // cookie, and a policy that files it under strictly necessary is wrong.
        $likely = collect($results['vendors'] ?? [])->flatMap(
            fn (array $vendor) => collect($vendor['cookies'])->map(fn (array $cookie) => [
                'name' => $cookie['name'],
                'provider' => $cookie['provider'],
                'duration' => $cookie['duration'],
                'purpose' => $cookie['purpose'],
                'category' => $vendor['category'],
            ])
        );

        return $observed
            ->merge($likely)
            ->unique('name')
            ->groupBy('category')
            ->sortKeysUsing(fn ($a, $b) => $this->order($a) <=> $this->order($b))
            ->map(fn (Collection $cookies) => $cookies
                ->sortBy(fn (array $cookie) => strtolower($cookie['name']))
                ->values());
    }

    /**
     * @param  array  $cookie
     * @return string
     */
    protected function purpose(array $cookie): string
    {
        return $cookie['purpose'] !== ''
            ? $cookie['purpose']
            : 'Describe this cookie before publishing.';
    }

    /**
     * @return string
     */
    protected function intro(): string
    {
        return 'A cookie is a small file a website stores on your device. We use the cookies below. You can change which optional ones you accept at any time.';
    }

    /**
     * @param  string  $category
     * @return int
     */
    protected function order(string $category): int
    {
        return match ($category) {
            'necessary' => 0,
            'functional' => 1,
            'analytics' => 2,
            'advertising' => 3,
            default => 4,
        };
    }
}
