<?php

namespace AltDesign\AltCookiesAddon\Tests;

use AltDesign\AltCookiesAddon\ServiceProvider;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // The addon checks the Statamic version as it boots, which happens before
        // AddonTestCase gets a chance to mock it out.
        \Facades\Statamic\Version::shouldReceive('get')->andReturn(
            ltrim(\Composer\InstalledVersions::getPrettyVersion('statamic/cms'), 'v')
        );

        // The settings helper reads content/alt-cookies/settings.yaml from the standard disk.
        $app['config']->set('filesystems.disks.local.root', $this->fixturePath());
        $app->bind('filesystems.paths.standard', fn () => $this->fixturePath());

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('app.name', 'Test Site');
        $app['config']->set('session.cookie', 'test_site_session');
        $app['config']->set('session.lifetime', 120);
    }

    protected function fixturePath(): string
    {
        return __DIR__.'/__fixtures__/storage';
    }

    protected function writeSettings(array $settings): void
    {
        File::ensureDirectoryExists($this->fixturePath().'/content/alt-cookies');

        File::put(
            $this->fixturePath().'/content/alt-cookies/settings.yaml',
            YAML::dump($settings)
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixturePath().'/content');
        File::deleteDirectory(storage_path('statamic/addons/alt-cookies'));

        // AddonTestCase points the Stache and the user repository at tests/__fixtures__,
        // so anything a test creates there is found by the next test unless it goes.
        File::deleteDirectory(__DIR__.'/__fixtures__/content');
        File::deleteDirectory(__DIR__.'/__fixtures__/users');

        parent::tearDown();
    }
}
