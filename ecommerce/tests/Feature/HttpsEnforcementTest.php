<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsEnforcementTest extends TestCase
{
    /**
     * Reset forceScheme after every test that may alter it.
     */
    protected function tearDown(): void
    {
        URL::forceScheme(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // TC-01  Middleware / provider layer
    // ---------------------------------------------------------------

    /** @test */
    public function nf004_app_environment_is_not_production_in_test_suite(): void
    {
        $this->assertNotEquals('production', $this->app->environment());
    }

    /** @test */
    public function nf004_https_is_NOT_forced_in_testing_environment(): void
    {
        // Boot a fresh provider in the current (testing) env — should NOT call forceScheme
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $url = URL::to('/some-path');

        // APP_URL = http://localhost in phpunit.xml → scheme stays http
        $this->assertStringStartsWith('http://', $url);
    }

    /** @test */
    public function nf004_https_IS_forced_when_app_environment_is_production(): void
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $url = URL::to('/some-path');

        $this->assertStringStartsWith('https://', $url);
    }

    /** @test */
    public function nf004_https_IS_forced_for_root_url_in_production(): void
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertStringStartsWith('https://', URL::to('/'));
    }

    /** @test */
    public function nf004_https_IS_forced_for_asset_urls_in_production(): void
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $asset = asset('css/app.css');

        $this->assertStringStartsWith('https://', $asset);
    }

    // ---------------------------------------------------------------
    // TC-06  URL facade — forceScheme mechanism verification
    // ---------------------------------------------------------------

    /** @test */
    public function nf004_url_forceScheme_changes_generated_url_scheme_to_https(): void
    {
        URL::forceScheme('https');

        $this->assertStringStartsWith('https://', URL::to('/check'));
    }

    /** @test */
    public function nf004_url_forceScheme_null_reverts_scheme_to_http(): void
    {
        URL::forceScheme('https');
        URL::forceScheme(null);

        $this->assertStringStartsWith('http://', URL::to('/check'));
    }

    /** @test */
    public function nf004_url_forceScheme_applies_to_named_routes(): void
    {
        URL::forceScheme('https');

        $url = route('login');

        $this->assertStringStartsWith('https://', $url);
    }

    // ---------------------------------------------------------------
    // TC-09  AppServiceProvider only acts on the URL facade
    // ---------------------------------------------------------------

    /** @test */
    public function nf004_provider_boot_in_staging_env_does_not_force_https(): void
    {
        $this->app['env'] = 'staging';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $url = URL::to('/staging-test');

        $this->assertStringStartsWith('http://', $url);
    }

    /** @test */
    public function nf004_provider_boot_in_local_env_does_not_force_https(): void
    {
        $this->app['env'] = 'local';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $url = URL::to('/local-test');

        $this->assertStringStartsWith('http://', $url);
    }

    /** @test */
    public function nf004_multiple_url_calls_in_production_all_use_https(): void
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertStringStartsWith('https://', URL::to('/page-one'));
        $this->assertStringStartsWith('https://', URL::to('/page-two'));
        $this->assertStringStartsWith('https://', URL::to('/page-three'));
    }

    /** @test */
    public function nf004_app_service_provider_boot_completes_within_one_second(): void
    {
        $this->app['env'] = 'production';

        $start = microtime(true);

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed);
    }
}
