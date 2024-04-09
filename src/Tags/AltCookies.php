<?php namespace AltDesign\AltCookiesAddon\Tags;

use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Vite;

use Statamic\Tags\Tags;
use Statamic\Filesystem\Manager;

use AltDesign\AltCookiesAddon\Helpers\Data;

class AltCookies extends Tags
{
    protected static $handle = 'AltCookies';

    /**
     * The {{ AltCookies:init }} tag.
     * Returns script to init the Frontend Manager from Vite
     * @return string|array
     */
    public function init()
    {
        $data = new Data('settings');
        $google = new Data('google');
        $return = [];
        $return[] = '<script>';
        // Read the file and swap out the tags
        $js = (new Manager)->disk()->get('addons/Alt-Cookies-Addon/resources/js/alt-cookies-init.js');
        $js = str_replace('{{ cookie_lifetime }}', $data->get('cookie_lifetime'), $js);
        $return[] = $js;
        $return[] = '</script>';
        return implode(' ', $return);
    }

    /**
     * The {{ AltCookies:google }} tag.
     * gtag.js stuff, put the tagID in etc.
     * @return string|array
     */
    public function google()
    {
        $data = new Data('settings');
        $gConf = new Data('google');

        // bail if google's disabled or there's no tag id
        if (!$data->get('enable_google') || !($gtagId = $data->get('google_tag_id'))) {
            return;
        }
        $return = [];
        $return[] = '<script>window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}</script>';
        $return[] = sprintf($gConf->get('gtag_js_formatter'), $gtagId); // Load gtag.js
        $return[] = sprintf($gConf->get('gtag_js_datalayer'), $gtagId); // Setup datalayer

        return implode(' ', $return);
    }

    /**
     * The {{ AltCookies:AddonAssets }} tag.
     * Puts the Vite assets on the frontent
     * @return string|array
     */
    public function AddonAssets()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-cookies-addon/build');
        $assets = sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/alt-cookies-addon.js'));
        $assets .= sprintf('<script type="module" src="%s"></script>', $vite->asset('resources/js/frontend-manager.js'));
        return $assets;
    }

    public function defaultCSS()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/alt-cookies-addon/build');
        $assets = sprintf('<link rel="stylesheet" href="%s"/>', $vite->asset('resources/css/alt-cookies-addon.css'));
        return $assets;
    }

    /**
     * The {{ AltCookies:cookieFields }} tag.
     * Read cookie, put the cp stuff into the front
     * @return string|array
     */
    public function cookieFields()
    {
        $return = [];
        $data = new Data('settings');

        $return[] = $data->get('necessary');

//        if (isset($_COOKIE['AltCookieAddon']) && $_COOKIE['AltCookieAddon'] == 'accepted') {
//            if ($data->get('enable_analytics')) {
//                $return[] = $data->get('analytics');
//            }
//            if ($data->get('enable_advertising')) {
//                $return[] = $data->get('advertising');
//            }
//        }
        switch($_COOKIE['AltCookieAddon'] ?? null) {
            case 4:
                if ($data->get('enable_advertising')) {
                    $return[] = $data->get('advertising');
                }
                // INTENTIONAL FALLTHROUGH;
            case 2:
                if ($data->get('enable_analytics')) {
                    $return[] = $data->get('analytics');
                }
                break;
            case 3:
                if ($data->get('enable_advertising')) {
                    $return[] = $data->get('advertising');
                }
                break;
            default :
                break;
        }

        return implode(' ', $return);

    }

    // Views
    public function Toast()
    {
        return view('alt-cookies-addon::consent');
    }

    public function Scripts()
    {
        return view('alt-cookies-addon::scripts');
    }

    // Support for customs
    public function reset()
    {
        return 'window.altCookies.resetConsent()';
    }

    public function accept()
    {
        return 'window.altCookies.userConsentGranted()';
    }

    public function decline()
    {
        return 'window.altCookies.userConsentDenied()';
    }
}
