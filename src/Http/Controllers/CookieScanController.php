<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Class CookieScanController
 *
 * The report itself lives on the Alt Cookies settings page, in a tab of its own.
 * This handles running and clearing a scan, from the scan tab over fetch and from
 * the dashboard widget as an ordinary form post.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class CookieScanController
{
    public function __construct(protected ScanStore $store)
    {
    }

    /**
     * @param  Request  $request
     * @param  CookieScanner  $scanner
     * @return JsonResponse|RedirectResponse
     */
    public function scan(Request $request, CookieScanner $scanner)
    {
        try {
            $results = $scanner->scan();
        } catch (Throwable $e) {
            $message = 'The scan could not be completed: '.$e->getMessage();

            return $request->expectsJson()
                ? response()->json(['message' => $message], 500)
                : $this->back($request)->with('error', $message);
        }

        $this->store->put($results);

        return $request->expectsJson()
            ? response()->json(['counts' => $results['counts']])
            : $this->back($request)->with('success', 'Scan complete.');
    }

    /**
     * @param  Request  $request
     * @return JsonResponse|RedirectResponse
     */
    public function clear(Request $request)
    {
        $this->store->clear();

        return $request->expectsJson()
            ? response()->json(['cleared' => true])
            : $this->back($request)->with('success', 'Scan results cleared.');
    }

    /**
     * Record cookies a deep scan saw in the browser.
     *
     * Names arrive from the control panel rather than from the site, so they are
     * validated and capped before anything is written, and urls are only accepted
     * if they were in the scan this is adding to.
     *
     * @param  Request  $request
     * @param  CookieScanner  $scanner
     * @return JsonResponse
     */
    public function observed(Request $request, CookieScanner $scanner): JsonResponse
    {
        $results = $this->store->get();

        if (! $results) {
            return response()->json(['message' => 'Run a scan before recording what a browser saw.'], 409);
        }

        $urls = collect($results['pages'] ?? [])->pluck('url')->filter()->all();

        $validated = $request->validate([
            'cookies' => ['present', 'array', 'max:500'],
            'cookies.*.name' => ['required', 'string', 'max:255', 'regex:/^[^\s,;=]+$/'],
            'cookies.*.url' => ['nullable', 'string', Rule::in($urls)],
        ]);

        $merged = $scanner->mergeBrowserObservations($results, $validated['cookies']);

        $this->store->put($merged);

        return response()->json(['counts' => $merged['counts']]);
    }

    /**
     * The scan used to have a page of its own. Anything still pointing at it lands
     * on the tab that replaced it.
     *
     * @return RedirectResponse
     */
    public function redirectToTab(): RedirectResponse
    {
        return redirect(cp_route('alt-cookies-addon.index').'#scan');
    }

    /**
     * Scans started from the dashboard widget return to the dashboard, so the button
     * does not move the reader somewhere they did not ask to go.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    protected function back(Request $request): RedirectResponse
    {
        return redirect($request->input('return') === 'dashboard'
            ? cp_route('dashboard')
            : cp_route('alt-cookies-addon.index').'#scan');
    }
}
