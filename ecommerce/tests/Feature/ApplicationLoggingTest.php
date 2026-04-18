<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * NF-009 — Application logs stored and monitored
 *
 * Laravel Telescope installed as dev dependency (local/dev).
 * Sentry SDK installed and configured (production monitoring).
 */
class ApplicationLoggingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // TC-01 — Telescope package is in require-dev
    // -------------------------------------------------------------------------
    public function test_nf009_tc01_telescope_package_is_installed_as_dev_dependency(): void
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $this->assertArrayHasKey(
            'laravel/telescope',
            $composer['require-dev'],
            'laravel/telescope must be a require-dev dependency'
        );
    }

    // -------------------------------------------------------------------------
    // TC-02 — Telescope config file exists
    // -------------------------------------------------------------------------
    public function test_nf009_tc02_telescope_config_file_exists(): void
    {
        $this->assertFileExists(
            config_path('telescope.php'),
            'config/telescope.php must exist after telescope:install'
        );
    }

    // -------------------------------------------------------------------------
    // TC-03 — Telescope is disabled in the test environment
    // -------------------------------------------------------------------------
    public function test_nf009_tc03_telescope_is_disabled_in_test_environment(): void
    {
        // phpunit.xml sets TELESCOPE_ENABLED=false
        $this->assertFalse(
            (bool) config('telescope.enabled'),
            'Telescope must be disabled during tests (TELESCOPE_ENABLED=false)'
        );
    }

    // -------------------------------------------------------------------------
    // TC-04 — Telescope migration file exists
    // -------------------------------------------------------------------------
    public function test_nf009_tc04_telescope_migration_file_exists(): void
    {
        $migrations = File::glob(database_path('migrations/*telescope*'));

        $this->assertNotEmpty(
            $migrations,
            'A telescope entries migration file must exist in database/migrations/'
        );
    }

    // -------------------------------------------------------------------------
    // TC-05 — TelescopeServiceProvider exists in app/Providers
    // -------------------------------------------------------------------------
    public function test_nf009_tc05_telescope_service_provider_exists(): void
    {
        $this->assertFileExists(
            app_path('Providers/TelescopeServiceProvider.php'),
            'app/Providers/TelescopeServiceProvider.php must exist'
        );
    }

    // -------------------------------------------------------------------------
    // TC-06 — TelescopeServiceProvider is registered in config/app.php
    // -------------------------------------------------------------------------
    public function test_nf009_tc06_telescope_service_provider_registered_in_app_config(): void
    {
        $appConfig = File::get(config_path('app.php'));

        $this->assertStringContainsString(
            'TelescopeServiceProvider',
            $appConfig,
            'TelescopeServiceProvider must be listed in config/app.php providers'
        );
    }

    // -------------------------------------------------------------------------
    // TC-07 — TelescopeServiceProvider extends TelescopeApplicationServiceProvider
    //          (ensuring env-based gating is inherited)
    // -------------------------------------------------------------------------
    public function test_nf009_tc07_telescope_service_provider_extends_application_service_provider(): void
    {
        $source = File::get(app_path('Providers/TelescopeServiceProvider.php'));

        $this->assertStringContainsString(
            'TelescopeApplicationServiceProvider',
            $source,
            'App TelescopeServiceProvider must extend TelescopeApplicationServiceProvider'
        );
    }

    // -------------------------------------------------------------------------
    // TC-08 — Sentry package is installed (not dev-only — needed in production)
    // -------------------------------------------------------------------------
    public function test_nf009_tc08_sentry_package_is_installed_as_production_dependency(): void
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $this->assertArrayHasKey(
            'sentry/sentry-laravel',
            $composer['require'],
            'sentry/sentry-laravel must be a production (require) dependency'
        );
    }

    // -------------------------------------------------------------------------
    // TC-09 — Sentry config file exists
    // -------------------------------------------------------------------------
    public function test_nf009_tc09_sentry_config_file_exists(): void
    {
        $this->assertFileExists(
            config_path('sentry.php'),
            'config/sentry.php must exist after vendor:publish'
        );
    }

    // -------------------------------------------------------------------------
    // TC-10 — Sentry DSN is read from environment variable
    // -------------------------------------------------------------------------
    public function test_nf009_tc10_sentry_dsn_is_read_from_env(): void
    {
        $source = File::get(config_path('sentry.php'));

        $this->assertStringContainsString(
            'SENTRY_LARAVEL_DSN',
            $source,
            'config/sentry.php must read DSN from SENTRY_LARAVEL_DSN env variable'
        );
    }

    // -------------------------------------------------------------------------
    // TC-11 — A `sentry` log channel is defined in config/logging.php
    // -------------------------------------------------------------------------
    public function test_nf009_tc11_sentry_log_channel_is_defined(): void
    {
        $channels = config('logging.channels');

        $this->assertArrayHasKey(
            'sentry',
            $channels,
            'A `sentry` log channel must be defined in config/logging.php'
        );
    }

    // -------------------------------------------------------------------------
    // TC-12 — Default log channel is configurable via LOG_CHANNEL env
    // -------------------------------------------------------------------------
    public function test_nf009_tc12_default_log_channel_is_configurable_via_env(): void
    {
        // Verify the config reads from LOG_CHANNEL env (standard Laravel practice)
        $source = File::get(config_path('logging.php'));

        $this->assertStringContainsString(
            'LOG_CHANNEL',
            $source,
            'config/logging.php default channel must be driven by LOG_CHANNEL env variable'
        );
    }
}
