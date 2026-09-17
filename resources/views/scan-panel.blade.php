<div class="alt-cookies-scan alt-cookies-scan--panel"
     data-alt-cookies-panel
     data-alt-cookies-csrf="{{ csrf_token() }}"
     data-alt-cookies-scan-url="{{ cp_route('alt-cookies-addon.scan.run') }}"
     data-alt-cookies-clear-url="{{ cp_route('alt-cookies-addon.scan.clear') }}"
     data-alt-cookies-observed-url="{{ cp_route('alt-cookies-addon.scan.observed') }}"
     data-alt-cookies-pages="{{ $results ? json_encode(collect($results['pages'])->where('ok', true)->pluck('url')->values()) : '[]' }}">

    <header class="alt-cookies-scan__header">
        <div>
            <p class="alt-cookies-scan__lede">
                Requests a sample of this site's pages as a visitor who accepted every category, reports
                the cookies those pages set, and names the third party services it recognises.
            </p>
        </div>

        <div class="alt-cookies-scan__actions">
            @if ($results)
                <button type="button" class="alt-cookies-scan__button alt-cookies-scan__button--quiet" data-alt-cookies-action="clear">Clear results</button>
                <button type="button" class="alt-cookies-scan__button alt-cookies-scan__button--quiet" data-alt-cookies-action="deep">Deep scan</button>
            @endif
            <button type="button" class="alt-cookies-scan__button" data-alt-cookies-action="scan">{{ $results ? 'Scan again' : 'Scan this site' }}</button>
        </div>
    </header>

    <p class="alt-cookies-scan__flash alt-cookies-scan__flash--error" data-alt-cookies-error hidden></p>
    <p class="alt-cookies-scan__flash" data-alt-cookies-status hidden></p>

    @if ($results)
        <details class="alt-cookies-scan__panel alt-cookies-scan__deep">
            <summary>What a deep scan does before you run one</summary>
            <p>
                The scan above reads response headers, so it sees what the server sets and nothing
                else. A deep scan loads each page in this browser, lets the scripts run, and reads
                the cookies they set. That is the only way to observe them rather than infer them.
            </p>
            <p>
                It loads the pages as a visitor who accepted everything, so the tracking runs for
                real. Your analytics will record a visit for each page, from you, and the cookies
                will be set in this browser. The cookies are cleaned up afterwards. The analytics
                hits cannot be taken back.
            </p>
            <p class="alt-cookies-scan__hint">
                Cookies set on another company's domain, such as DoubleClick or Facebook, stay in
                the likely list either way. A page cannot read those.
            </p>
        </details>
    @endif

        @if (! $results)
            <div class="alt-cookies-scan__panel alt-cookies-scan__empty">
                <p>No scan has been run yet.</p>
                <p>
                    A scan requests up to {{ config('alt-cookies.scan.max_pages', 15) }} of this site's own pages
                    and reads the cookies in the responses. It takes a few seconds and changes nothing.
                </p>
            </div>
        @else
            @php
                $observed = collect($results['observed']);
                $known = $observed->where('known', true);
                $unknown = $observed->where('known', false);
                $vendors = collect($results['vendors']);
                $failures = collect($results['pages'])->where('ok', false);
                $labels = [
                    'necessary' => 'Necessary',
                    'functional' => 'Functional',
                    'analytics' => 'Analytics',
                    'advertising' => 'Advertising',
                    'unknown' => 'Unrecognised',
                ];
            @endphp

            <div class="alt-cookies-scan__summary">
                <div class="alt-cookies-scan__stat">
                    <span class="alt-cookies-scan__stat-value">{{ $results['counts']['pages'] }}</span>
                    <span class="alt-cookies-scan__stat-label">pages scanned</span>
                </div>
                <div class="alt-cookies-scan__stat">
                    <span class="alt-cookies-scan__stat-value">{{ $results['counts']['observed'] }}</span>
                    <span class="alt-cookies-scan__stat-label">cookies observed</span>
                </div>
                <div class="alt-cookies-scan__stat">
                    <span class="alt-cookies-scan__stat-value">{{ $results['counts']['vendors'] }}</span>
                    <span class="alt-cookies-scan__stat-label">services recognised</span>
                </div>
                <div class="alt-cookies-scan__stat">
                    <span class="alt-cookies-scan__stat-value">{{ $results['counts']['unknown'] }}</span>
                    <span class="alt-cookies-scan__stat-label">not recognised</span>
                </div>
                <p class="alt-cookies-scan__timestamp">
                    Last scanned {{ \Illuminate\Support\Carbon::parse($results['scanned_at'])->diffForHumans() }}
                </p>
            </div>

            @if ($failures->isNotEmpty())
                <div class="alt-cookies-scan__panel alt-cookies-scan__panel--warning">
                    <h2>{{ $failures->count() }} {{ \Illuminate\Support\Str::plural('page', $failures->count()) }} could not be fetched</h2>
                    <ul class="alt-cookies-scan__failures">
                        @foreach ($failures as $failure)
                            <li>
                                <code>{{ $failure['url'] }}</code>
                                <span>{{ $failure['error'] ?? 'HTTP '.$failure['status'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="alt-cookies-scan__hint">
                        A local site served over https with a development certificate is the usual cause. Set
                        <code>ALT_COOKIES_SCAN_VERIFY_SSL=false</code> in your local environment, or publish the
                        addon config and turn <code>scan.verify_ssl</code> off there.
                    </p>
                </div>
            @endif

            <section class="alt-cookies-scan__section">
                <h2>Observed</h2>
                <p class="alt-cookies-scan__note">
                    Cookies a scan has actually seen, either in a response header or in the browser during a
                    deep scan. Confirmed rather than inferred.
                </p>

                @if ($observed->isEmpty())
                    <div class="alt-cookies-scan__panel">
                        <p>No cookies were seen. Nothing is set in a response header, and a deep scan has either not been run or found nothing.</p>
                    </div>
                @else
                    <table class="alt-cookies-scan__table">
                        <thead>
                            <tr>
                                <th>Cookie</th>
                                <th>Category</th>
                                <th>Provider</th>
                                <th>Lifetime</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($observed as $cookie)
                                <tr>
                                    <td>
                                        <code class="alt-cookies-scan__name">{{ $cookie['name'] }}</code>
                                        @if ($cookie['flags'])
                                            <span class="alt-cookies-scan__flags">{{ implode(' · ', $cookie['flags']) }}</span>
                                        @endif
                                        <span class="alt-cookies-scan__seen alt-cookies-scan__seen--{{ $cookie['source'] ?? 'header' }}">
                                            @switch($cookie['source'] ?? 'header')
                                                @case('browser') Seen in the browser @break
                                                @case('both') Seen in a header and in the browser @break
                                                @default Seen in a response header
                                            @endswitch
                                        </span>
                                        <details class="alt-cookies-scan__where">
                                            <summary>{{ count($cookie['urls']) }} {{ \Illuminate\Support\Str::plural('page', count($cookie['urls'])) }}</summary>
                                            <ul>
                                                @foreach ($cookie['urls'] as $url)
                                                    <li><code>{{ $url }}</code></li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </td>
                                    <td>
                                        <span class="alt-cookies-scan__badge alt-cookies-scan__badge--{{ $cookie['category'] }}">
                                            {{ $labels[$cookie['category']] ?? $cookie['category'] }}
                                        </span>
                                    </td>
                                    <td>{{ $cookie['provider'] }}</td>
                                    <td>
                                        {{ $cookie['observed_duration'] }}
                                        @if ($cookie['known'] && ! str_contains($cookie['duration'], $cookie['observed_duration']))
                                            <span class="alt-cookies-scan__muted">documented as {{ $cookie['duration'] }}</span>
                                        @endif
                                    </td>
                                    <td class="alt-cookies-scan__purpose">
                                        {{ $cookie['purpose'] ?: 'Not in the catalogue. Check what sets this before writing it into a cookie policy.' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="alt-cookies-scan__section">
                <h2>Likely</h2>
                @php
                    $observedNames = $observed->pluck('name');
                    $isObserved = fn (string $name) => $observedNames->contains(
                        fn (string $seen) => $seen === $name || \Illuminate\Support\Str::is($name, $seen)
                    );
                @endphp

                <p class="alt-cookies-scan__note">
                    Third party services found in the page markup and in the Alt Cookies script fields, with the
                    cookies each is documented to set. Anything a scan has actually seen is listed above instead.
                    What is left here either needs a deep scan to observe, or is set on the service's own domain
                    where no page can read it.
                </p>

                @if ($vendors->isEmpty())
                    <div class="alt-cookies-scan__panel">
                        <p>No third party services were recognised. Either there are none, or they are loaded by a tag manager container the scan cannot see into.</p>
                    </div>
                @else
                    @foreach ($vendors as $vendor)
                        <div class="alt-cookies-scan__vendor">
                            <div class="alt-cookies-scan__vendor-head">
                                <h3>{{ $vendor['name'] }}</h3>
                                <span class="alt-cookies-scan__badge alt-cookies-scan__badge--{{ $vendor['category'] }}">
                                    {{ $labels[$vendor['category']] ?? $vendor['category'] }}
                                </span>
                            </div>

                            @if ($vendor['note'])
                                <p class="alt-cookies-scan__hint">{{ $vendor['note'] }}</p>
                            @endif

                            <details class="alt-cookies-scan__where">
                                <summary>Found in {{ count($vendor['sources']) }} {{ \Illuminate\Support\Str::plural('place', count($vendor['sources'])) }}</summary>
                                <ul>
                                    @foreach ($vendor['sources'] as $source)
                                        <li><code>{{ $source }}</code></li>
                                    @endforeach
                                </ul>
                            </details>

                            @php
                                $unobserved = collect($vendor['cookies'])->reject(fn (array $c) => $isObserved($c['name']));
                                $alreadySeen = count($vendor['cookies']) - $unobserved->count();
                            @endphp

                            @if ($alreadySeen)
                                <p class="alt-cookies-scan__hint">
                                    {{ $alreadySeen }} of this service's {{ \Illuminate\Support\Str::plural('cookie', count($vendor['cookies'])) }}
                                    {{ $alreadySeen === 1 ? 'has' : 'have' }} been observed and {{ $alreadySeen === 1 ? 'is' : 'are' }} listed above.
                                </p>
                            @endif

                            @if ($unobserved->isNotEmpty())
                                <table class="alt-cookies-scan__table alt-cookies-scan__table--nested">
                                    <tbody>
                                        @foreach ($unobserved as $cookie)
                                            <tr>
                                                <td><code class="alt-cookies-scan__name">{{ $cookie['name'] }}</code></td>
                                                <td>{{ $cookie['duration'] }}</td>
                                                <td class="alt-cookies-scan__purpose">{{ $cookie['purpose'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                @endif
            </section>

            <section class="alt-cookies-scan__section">
                <h2>Cookie policy</h2>
                <p class="alt-cookies-scan__note">
                    The scan written up as policy copy, observed and likely together, because a policy
                    describes what may be set rather than what one request happened to see. Paste it into
                    your cookie policy page and edit the wording around it. Nothing here is legal advice,
                    and anything the scan could not identify is marked for you to describe.
                </p>

                <div class="alt-cookies-scan__policy">
                    <div class="alt-cookies-scan__policy-actions">
                        <button type="button" class="alt-cookies-scan__button" data-alt-cookies-copy="markdown">Copy as Markdown</button>
                        <button type="button" class="alt-cookies-scan__button alt-cookies-scan__button--quiet" data-alt-cookies-copy="html">Copy as HTML</button>
                        <span class="alt-cookies-scan__copied" data-alt-cookies-copied hidden>Copied</span>
                    </div>

                    <textarea class="alt-cookies-scan__policy-text" data-alt-cookies-policy="markdown" rows="18" readonly>{{ $policyMarkdown }}</textarea>
                    <textarea data-alt-cookies-policy="html" hidden readonly>{{ $policyHtml }}</textarea>
                </div>
            </section>

            <section class="alt-cookies-scan__section">
                <details class="alt-cookies-scan__panel">
                    <summary>Pages requested in this scan</summary>
                    <ul class="alt-cookies-scan__pages">
                        @foreach ($results['pages'] as $page)
                            <li>
                                <code>{{ $page['url'] }}</code>
                                <span class="alt-cookies-scan__muted">{{ $page['error'] ?? $page['status'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            </section>
        @endif

</div>
