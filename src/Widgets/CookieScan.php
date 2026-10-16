<?php namespace AltDesign\AltCookiesAddon\Widgets;

use AltDesign\AltCookiesAddon\Support\ScanStore;
use Statamic\Facades\User;
use Statamic\Widgets\Widget;

/**
 * Class CookieScan
 *
 * Dashboard widget summarising the last cookie scan.
 *
 * Add it to the dashboard in config/statamic/cp.php:
 *
 *     'widgets' => [
 *         ['type' => 'alt_cookies_scan', 'width' => 50],
 *     ],
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class CookieScan extends Widget
{
    protected static $handle = 'alt_cookies_scan';

    /**
     * @return \Illuminate\Contracts\View\View|null
     */
    public function html()
    {
        if (! User::current()?->can('view alt-cookies-addon')) {
            return null;
        }

        return view('alt-cookies::widget', [
            'title' => $this->config('title', 'Cookie Scan'),
            'results' => app(ScanStore::class)->get(),
        ]);
    }
}
