<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use AltDesign\AltCookiesAddon\Support\CookieScanner;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
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
    public function __construct(protected ScanStore $store)
    {
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('alt-cookies::scan', [
            'results' => $this->store->get(),
        ]);
    }

    /**
     * @param  CookieScanner  $scanner
     * @return RedirectResponse
     */
    public function scan(CookieScanner $scanner): RedirectResponse
    {
        try {
            $results = $scanner->scan();
        } catch (Throwable $e) {
            return redirect(cp_route('alt-cookies-addon.scan.index'))
                ->with('error', 'The scan could not be completed: '.$e->getMessage());
        }

        $this->store->put($results);

        return redirect(cp_route('alt-cookies-addon.scan.index'))
            ->with('success', 'Scan complete.');
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
}
