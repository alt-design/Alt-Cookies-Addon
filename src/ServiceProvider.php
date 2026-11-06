<?php namespace AltDesign\AltCookiesAddon;

use AltDesign\AltCookiesAddon\Support\CookieCatalogue;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use AltDesign\AltCookiesAddon\Widgets\CookieScan;
use Illuminate\Support\Str;
use Facades\Statamic\Version;
use AltDesign\AltCookiesAddon\Support\AltToolsNav;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;


class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];
    protected $tags = [
        \AltDesign\AltCookiesAddon\Tags\AltCookies::class,
    ];

    protected $widgets = [
        CookieScan::class,
    ];

    protected $publishables = [
        __DIR__.'/../resources/dist' => '',
    ];

    protected $stylesheets = [
        __DIR__.'/../resources/css/cp.css',
    ];

    protected $scripts = [
        __DIR__.'/../resources/js/cp.js',
    ];

    protected $vite = [
        'input' => [
            'resources/js/alt-cookies-addon.js',
            'resources/js/frontend-manager.js',
            'resources/css/alt-cookies-addon.css'
        ],
        'publicDirectory' => 'resources/dist',
    ];

    /**
     * The catalogue reads two YAML files and the store is stateless, so neither
     * needs building more than once per request.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->singleton(CookieCatalogue::class);
        $this->app->singleton(ScanStore::class);
    }

    /**
     * Add to the nav so it appears in the CP.
     *
     * @return self
     */
    public function addToNav() : self
    {
        Nav::extend(function ($nav) {
            AltToolsNav::add($nav, 'privacy', 'Alt Cookies', 'alt-cookies-addon.index', 'view alt-cookies-addon', ['eye-slash', 'hidden']);
        });

        return $this;
    }

    /**
     * Register our permissions, so we can control who can see the settings.
     *
     * @return self
     */
    public function registerPermissions() : self
    {
        Permission::register('view alt-cookies-addon')
            ->label('View Alt Cookies Settings');
        return $this;
    }

    /**
     * Main startup method.
     *
     * @return void
     */
    public function bootAddon()
    {
        $this->addToNav()
            ->registerPermissions();

        // Statamic >= V6 - unbind the settings blueprint to remove the default settings page and permissions 
        // as we are handling this manually instead
        if(intval(Str::before(Version::get(), '.')) >= 6) {
            app()->offsetUnset("statamic.addons.alt-cookies.settings_blueprint");
        }
    }
}
