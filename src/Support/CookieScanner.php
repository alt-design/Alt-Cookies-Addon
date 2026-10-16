<?php namespace AltDesign\AltCookiesAddon\Support;

use AltDesign\AltCookiesAddon\Helpers\Data;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Throwable;

/**
 * Class CookieScanner
 *
 * Requests a sample of this site's own pages and reports the cookies they set,
 * alongside the third party services recognisable in the markup. Cookies set by
 * JavaScript cannot be observed this way, so those are reported as likely rather
 * than confirmed.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class CookieScanner
{
    public const CONSENT_COOKIE = 'AltCookieAddon';

    public const FULL_CONSENT = '4';

    public function __construct(protected CookieCatalogue $catalogue)
    {
    }

    /**
     * Run a scan and return the results.
     *
     * @return array
     */
    public function scan(): array
    {
        $urls = $this->urls();
        $responses = $this->fetch($urls);

        $pages = [];
        $documents = [];
        $cookies = [];

        foreach ($urls as $url) {
            $response = $responses[$url] ?? null;

            if (! $response instanceof Response) {
                $pages[] = [
                    'url' => $url,
                    'status' => null,
                    'ok' => false,
                    'error' => $response instanceof Throwable
                        ? $response->getMessage()
                        : 'No response.',
                ];

                continue;
            }

            $pages[] = [
                'url' => $url,
                'status' => $response->status(),
                'ok' => $response->successful(),
                'error' => null,
            ];

            $documents[$url] = $response->body();

            foreach ($this->cookiesFrom($response) as $cookie) {
                $cookies[] = $cookie + ['url' => $url];
            }
        }

        $observed = $this->describeObserved($cookies);
        $vendors = $this->detectVendors($documents + $this->configuredScripts());

        return [
            'scanned_at' => Carbon::now()->toIso8601String(),
            'pages' => $pages,
            'observed' => $observed->all(),
            'vendors' => $vendors->all(),
            'counts' => [
                'pages' => count(array_filter($pages, fn (array $page) => $page['ok'])),
                'failed' => count(array_filter($pages, fn (array $page) => ! $page['ok'])),
                'observed' => $observed->count(),
                'unknown' => $observed->where('known', false)->count(),
                'vendors' => $vendors->count(),
            ],
        ];
    }

    /**
     * The pages to request, capped so a scan cannot run away on a large site.
     *
     * @return array<int, string>
     */
    public function urls(): array
    {
        $site = Site::current();
        $urls = collect([$site->absoluteUrl()]);

        foreach (Collection::all() as $collection) {
            if (! $collection->route($site->handle())) {
                continue;
            }

            $urls = $urls->merge(
                Entry::query()
                    ->where('collection', $collection->handle())
                    ->where('site', $site->handle())
                    ->where('published', true)
                    ->limit((int) config('alt-cookies.scan.per_collection', 5))
                    ->get()
                    ->map(fn ($entry) => $entry->absoluteUrl())
            );
        }

        return $urls
            ->filter()
            ->unique()
            ->take((int) config('alt-cookies.scan.max_pages', 15))
            ->values()
            ->all();
    }

    /**
     * Request every page at once, as a visitor who accepted every category.
     *
     * @param  array<int, string>  $urls
     * @return array<string, Response|Throwable>
     */
    protected function fetch(array $urls): array
    {
        if (empty($urls)) {
            return [];
        }

        $timeout = (int) config('alt-cookies.scan.timeout', 10);
        $verify = (bool) config('alt-cookies.scan.verify_ssl', true);
        $agent = (string) config('alt-cookies.scan.user_agent', 'AltCookiesScanner/1.0');

        return Http::pool(fn ($pool) => array_map(
            fn (string $url) => $pool
                ->as($url)
                ->withHeaders(['User-Agent' => $agent])
                ->withCookies(
                    [self::CONSENT_COOKIE => self::FULL_CONSENT],
                    parse_url($url, PHP_URL_HOST) ?: ''
                )
                ->withOptions(['verify' => $verify, 'allow_redirects' => ['max' => 3]])
                ->timeout($timeout)
                ->get($url),
            $urls
        ));
    }

    /**
     * Pull the Set-Cookie headers off a response and parse each one.
     *
     * @param  Response  $response
     * @return array<int, array>
     */
    protected function cookiesFrom(Response $response): array
    {
        $headers = [];

        foreach ($response->headers() as $header => $values) {
            if (strtolower($header) === 'set-cookie') {
                $headers = $values;
                break;
            }
        }

        return collect($headers)
            ->map(fn (string $header) => $this->parseSetCookie($header))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  string  $header
     * @return array|null
     */
    protected function parseSetCookie(string $header): ?array
    {
        $parts = array_map('trim', explode(';', $header));
        $pair = array_shift($parts);

        if (! $pair || ! str_contains($pair, '=')) {
            return null;
        }

        [$name] = explode('=', $pair, 2);
        $attributes = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $attributes[strtolower(trim($key))] = trim($value);

                continue;
            }

            $attributes[strtolower($part)] = true;
        }

        return ['name' => trim($name), 'attributes' => $attributes];
    }

    /**
     * Group the raw observations by cookie name and describe each one.
     *
     * @param  array<int, array>  $cookies
     * @return SupportCollection<int, array>
     */
    protected function describeObserved(array $cookies): SupportCollection
    {
        return collect($cookies)
            ->groupBy('name')
            ->map(function (SupportCollection $group, string $name) {
                $known = $this->catalogue->identify($name);
                $attributes = $group->first()['attributes'];

                return [
                    'name' => $name,
                    'source' => 'header',
                    'known' => (bool) $known,
                    'provider' => $known['provider'] ?? 'Unknown',
                    'category' => $known['category'] ?? 'unknown',
                    'duration' => $known['duration'] ?? 'Unknown',
                    'purpose' => $known['purpose'] ?? '',
                    'observed_duration' => $this->observedDuration($attributes),
                    'flags' => $this->flags($attributes),
                    'urls' => $group->pluck('url')->unique()->values()->all(),
                ];
            })
            ->sortBy($this->byCategoryThenName())
            ->values();
    }

    /**
     * Fold cookies seen in a real browser into a stored scan.
     *
     * A browser only hands over names and values through document.cookie, so these
     * carry no attributes and no lifetime. What they do carry is certainty: the
     * cookie was set, rather than inferred from a script being on the page.
     *
     * Each entry is expected to be ['name' => string, 'url' => string|null], but the
     * list arrives from the control panel over fetch, so it is checked rather than
     * trusted.
     *
     * @param  array  $results
     * @param  array<int, mixed>  $cookies
     * @param  array<int, string>  $blocked  Pages the browser could not load
     * @return array
     */
    public function mergeBrowserObservations(array $results, array $cookies, array $blocked = []): array
    {
        $seen = collect($cookies)
            ->filter(fn ($cookie) => is_array($cookie) && ! empty($cookie['name']))
            ->groupBy('name');

        $observed = collect($results['observed'] ?? [])->keyBy('name');

        foreach ($seen as $name => $group) {
            $urls = $group->pluck('url')->filter()->unique()->values()->all();

            if ($existing = $observed->get($name)) {
                $observed->put($name, array_merge($existing, [
                    'source' => 'both',
                    'urls' => collect($existing['urls'])->merge($urls)->unique()->values()->all(),
                ]));

                continue;
            }

            $known = $this->catalogue->identify($name);

            $observed->put($name, [
                'name' => $name,
                'source' => 'browser',
                'known' => (bool) $known,
                'provider' => $known['provider'] ?? 'Unknown',
                'category' => $known['category'] ?? 'unknown',
                'duration' => $known['duration'] ?? 'Unknown',
                'purpose' => $known['purpose'] ?? '',
                'observed_duration' => 'Not visible to a browser scan',
                'flags' => [],
                'urls' => $urls,
            ]);
        }

        $observed = $observed->values()->sortBy($this->byCategoryThenName())->values();

        $results['observed'] = $observed->all();
        $results['browser_scanned_at'] = Carbon::now()->toIso8601String();
        $results['browser_blocked'] = array_values(array_unique($blocked));
        $results['counts']['observed'] = $observed->count();
        $results['counts']['unknown'] = $observed->where('known', false)->count();
        $results['counts']['browser'] = $observed->whereIn('source', ['browser', 'both'])->count();
        $results['counts']['blocked'] = count($results['browser_blocked']);

        return $results;
    }

    /**
     * How long the cookie actually asked to live, which is not always what the
     * provider documents.
     *
     * @param  array  $attributes
     * @return string
     */
    protected function observedDuration(array $attributes): string
    {
        if (isset($attributes['max-age'])) {
            $seconds = (int) $attributes['max-age'];

            return $seconds <= 0 ? 'Expires immediately' : $this->humanise($seconds);
        }

        if (isset($attributes['expires']) && is_string($attributes['expires'])) {
            try {
                $expires = Carbon::parse($attributes['expires']);
            } catch (Throwable) {
                return 'Unknown';
            }

            return $this->humanise(max(0, $expires->getTimestamp() - Carbon::now()->getTimestamp()));
        }

        return 'Session';
    }

    /**
     * @param  int  $seconds
     * @return string
     */
    protected function humanise(int $seconds): string
    {
        return match (true) {
            $seconds < 60 => $seconds.' seconds',
            $seconds < 3600 => round($seconds / 60).' minutes',
            $seconds < 86400 => round($seconds / 3600).' hours',
            $seconds < 31536000 => round($seconds / 86400).' days',
            default => round($seconds / 31536000, 1).' years',
        };
    }

    /**
     * The security attributes worth showing next to a cookie.
     *
     * @param  array  $attributes
     * @return array<int, string>
     */
    protected function flags(array $attributes): array
    {
        $flags = [];

        if (isset($attributes['secure'])) {
            $flags[] = 'Secure';
        }

        if (isset($attributes['httponly'])) {
            $flags[] = 'HttpOnly';
        }

        if (isset($attributes['samesite']) && is_string($attributes['samesite'])) {
            $flags[] = 'SameSite='.Str::ucfirst(strtolower($attributes['samesite']));
        }

        return $flags;
    }

    /**
     * The addon's own script fields, so tracking sitting behind consent is found
     * even though the scan never sees it rendered.
     *
     * @return array<string, string>
     */
    protected function configuredScripts(): array
    {
        $settings = new Data('settings');

        $fields = [
            'Necessary scripts (Alt Cookies settings)' => $settings->get('necessary'),
            'Analytics scripts (Alt Cookies settings)' => $settings->get('analytics'),
            'Advertising scripts (Alt Cookies settings)' => $settings->get('advertising'),
        ];

        if ($settings->get('enable_google') && $settings->get('google_tag_id')) {
            $fields['Google Tag ID (Alt Cookies settings)'] = 'https://www.googletagmanager.com/gtag/js?id='.$settings->get('google_tag_id');
        }

        return array_filter($fields, fn ($value) => is_string($value) && $value !== '');
    }

    /**
     * Match every known vendor signature against each document.
     *
     * @param  array<string, string>  $documents
     * @return SupportCollection<int, array>
     */
    protected function detectVendors(array $documents): SupportCollection
    {
        $haystacks = array_map('strtolower', $documents);

        return $this->catalogue->vendors()
            ->map(function (array $vendor) use ($haystacks) {
                $sources = [];

                foreach ($haystacks as $source => $content) {
                    foreach ($vendor['signatures'] as $signature) {
                        if (str_contains($content, strtolower($signature))) {
                            $sources[] = $source;
                            break;
                        }
                    }
                }

                if (empty($sources)) {
                    return null;
                }

                return [
                    'name' => $vendor['name'],
                    'category' => $vendor['category'],
                    'note' => $vendor['note'] ?? null,
                    'sources' => $sources,
                    'cookies' => $this->catalogue->describeAll($vendor['cookies'])->all(),
                ];
            })
            ->filter()
            ->sortBy($this->byCategoryThenName())
            ->values();
    }

    /**
     * Comparators for sortBy. Inside its array form a closure is a comparator
     * taking two items, not a callback returning a sort key.
     *
     * @return array<int, callable>
     */
    protected function byCategoryThenName(): array
    {
        return [
            fn (array $a, array $b) => $this->categoryOrder($a['category']) <=> $this->categoryOrder($b['category']),
            fn (array $a, array $b) => strcasecmp($a['name'], $b['name']),
        ];
    }

    /**
     * @param  string  $category
     * @return int
     */
    protected function categoryOrder(string $category): int
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
