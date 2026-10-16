<?php namespace AltDesign\AltCookiesAddon\Support;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Throwable;

/**
 * Class ScanStore
 *
 * Keeps the most recent scan so the control panel has something to show when
 * the page is reopened. Scan results are a diagnostic rather than content, so
 * they live in storage and stay out of the content directory and git.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class ScanStore
{
    /**
     * @return string
     */
    public function path(): string
    {
        return storage_path('statamic/addons/alt-cookies/scan.yaml');
    }

    /**
     * The last scan, or null when the site has never been scanned.
     *
     * @return array|null
     */
    public function get(): ?array
    {
        if (! File::exists($this->path())) {
            return null;
        }

        try {
            $results = YAML::parse(File::get($this->path()));
        } catch (Throwable) {
            return null;
        }

        return isset($results['scanned_at']) ? $results : null;
    }

    /**
     * @param  array  $results
     * @return void
     */
    public function put(array $results): void
    {
        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), YAML::dump($results));
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        File::delete($this->path());
    }
}
