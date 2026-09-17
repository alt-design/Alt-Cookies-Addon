<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\PolicyWriter;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Throwable;

/**
 * Class CookieScanController
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class CookieScanController
{
    public function __construct(
        protected ScanStore $store,
        protected PolicyWriter $policy
    ) {
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $results = $this->store->get();

        return view('alt-cookies::scan', [
            'results' => $results,
            'policyMarkdown' => $results ? $this->policy->markdown($results) : null,
            'policyHtml' => $results ? $this->policy->html($results) : null,
        ]);
    }

    /**
     * @param  Request  $request
     * @param  CookieScanner  $scanner
     * @return RedirectResponse
     */
    public function scan(Request $request, CookieScanner $scanner): RedirectResponse
    {
        try {
            $results = $scanner->scan();
        } catch (Throwable $e) {
            return $this->back($request)
                ->with('error', 'The scan could not be completed: '.$e->getMessage());
        }

        $this->store->put($results);

        return $this->back($request)->with('success', 'Scan complete.');
    }

    /**
     * @return RedirectResponse
     */
    public function clear(): RedirectResponse
    {
        $this->store->clear();

        return redirect(cp_route('alt-cookies-addon.scan.index'))
            ->with('success', 'Scan results cleared.');
    }

    /**
     * Scans started from the dashboard widget return to the dashboard, so the
     * button does not move the reader somewhere they did not ask to go.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    protected function back(Request $request): RedirectResponse
    {
        return redirect($request->input('return') === 'dashboard'
            ? cp_route('dashboard')
            : cp_route('alt-cookies-addon.scan.index'));
    }
}
