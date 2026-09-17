<div class="alt-cookies-widget">
    <div class="alt-cookies-widget__head">
        <h2>{{ $title }}</h2>
        <form method="POST" action="{{ cp_route('alt-cookies-addon.scan.run') }}">
            @csrf
            <input type="hidden" name="return" value="dashboard">
            <button type="submit" class="alt-cookies-widget__button">{{ $results ? 'Scan again' : 'Scan now' }}</button>
        </form>
    </div>

    @if (! $results)
        <p class="alt-cookies-widget__empty">
            No scan has been run. A scan requests a sample of this site's pages and reports the
            cookies they set, and the third party services it recognises.
        </p>
    @else
        <div class="alt-cookies-widget__stats">
            <div>
                <span class="alt-cookies-widget__value">{{ $results['counts']['observed'] }}</span>
                <span class="alt-cookies-widget__label">cookies observed</span>
            </div>
            <div>
                <span class="alt-cookies-widget__value">{{ $results['counts']['vendors'] }}</span>
                <span class="alt-cookies-widget__label">services recognised</span>
            </div>
            <div>
                <span class="alt-cookies-widget__value">{{ $results['counts']['unknown'] }}</span>
                <span class="alt-cookies-widget__label">not recognised</span>
            </div>
        </div>

        @if ($results['counts']['failed'])
            <p class="alt-cookies-widget__warning">{{ $results['counts']['failed'] }} {{ \Illuminate\Support\Str::plural('page', $results['counts']['failed']) }} could not be fetched on the last scan.</p>
        @endif

        <p class="alt-cookies-widget__foot">Last scanned {{ \Illuminate\Support\Carbon::parse($results['scanned_at'])->diffForHumans() }} across {{ $results['counts']['pages'] }} {{ \Illuminate\Support\Str::plural('page', $results['counts']['pages']) }}.</p>
    @endif

    <a class="alt-cookies-widget__link" href="{{ cp_route('alt-cookies-addon.scan.index') }}">
        {{ $results ? 'View full results' : 'Open the scan page' }}
    </a>
</div>
