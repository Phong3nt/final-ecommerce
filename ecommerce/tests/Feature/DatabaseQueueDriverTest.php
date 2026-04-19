<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * IMP-008 — Switch queue driver from sync to database [INFRA_MODE]
 *
 * Verifies:
 *   1. config/queue.php default is 'database' (source-level check).
 *   2. config/queue.php reads QUEUE_CONNECTION from env().
 *   3. .env.example documents QUEUE_CONNECTION=database.
 *   4. 'database' connection config specifies 'jobs' table.
 *   5. 'database' connection config has retry_after set.
 *   6. 'database' connection driver is 'database'.
 *   7. jobs table migration file exists in migrations folder.
 *   8. failed_jobs table migration file exists in migrations folder.
 *   9. jobs table is present in database schema (migrations ran).
 *  10. failed_jobs table is present in database schema (migrations ran).
 */
class DatabaseQueueDriverTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // TC-01: config/queue.php hard-coded fallback default is 'database'
    // -------------------------------------------------------------------------

    public function test_imp008_tc01_queue_config_fallback_default_is_database(): void
    {
        $contents = file_get_contents(config_path('queue.php'));

        $this->assertStringContainsString(
            "env('QUEUE_CONNECTION', 'database')",
            $contents,
            "config/queue.php fallback must be 'database', not 'sync'."
        );
    }

    // -------------------------------------------------------------------------
    // TC-02: config/queue.php reads QUEUE_CONNECTION from env
    // -------------------------------------------------------------------------

    public function test_imp008_tc02_queue_config_reads_queue_connection_from_env(): void
    {
        $contents = file_get_contents(config_path('queue.php'));

        $this->assertStringContainsString(
            "env('QUEUE_CONNECTION'",
            $contents,
            "config/queue.php must read QUEUE_CONNECTION from env."
        );
    }

    // -------------------------------------------------------------------------
    // TC-03: .env.example documents QUEUE_CONNECTION=database
    // -------------------------------------------------------------------------

    public function test_imp008_tc03_env_example_specifies_database_queue_connection(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString(
            'QUEUE_CONNECTION=database',
            $envExample,
            '.env.example must set QUEUE_CONNECTION=database.'
        );
    }

    // -------------------------------------------------------------------------
    // TC-04: .env.example does NOT still default to sync
    // -------------------------------------------------------------------------

    public function test_imp008_tc04_env_example_does_not_specify_sync_queue_connection(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringNotContainsString(
            'QUEUE_CONNECTION=sync',
            $envExample,
            '.env.example must not retain QUEUE_CONNECTION=sync.'
        );
    }

    // -------------------------------------------------------------------------
    // TC-05: 'database' connection config specifies 'jobs' table
    // -------------------------------------------------------------------------

    public function test_imp008_tc05_database_connection_config_specifies_jobs_table(): void
    {
        $table = config('queue.connections.database.table');

        $this->assertSame(
            'jobs',
            $table,
            "queue.connections.database.table must be 'jobs'."
        );
    }

    // -------------------------------------------------------------------------
    // TC-06: 'database' connection config has retry_after configured
    // -------------------------------------------------------------------------

    public function test_imp008_tc06_database_connection_config_has_retry_after(): void
    {
        $retryAfter = config('queue.connections.database.retry_after');

        $this->assertNotNull(
            $retryAfter,
            "queue.connections.database.retry_after must be set."
        );
        $this->assertIsInt($retryAfter);
        $this->assertGreaterThan(0, $retryAfter);
    }

    // -------------------------------------------------------------------------
    // TC-07: 'database' connection driver value is 'database'
    // -------------------------------------------------------------------------

    public function test_imp008_tc07_database_connection_driver_is_database(): void
    {
        $driver = config('queue.connections.database.driver');

        $this->assertSame(
            'database',
            $driver,
            "queue.connections.database.driver must be 'database'."
        );
    }

    // -------------------------------------------------------------------------
    // TC-08: jobs table migration file exists
    // -------------------------------------------------------------------------

    public function test_imp008_tc08_jobs_table_migration_file_exists(): void
    {
        $migrationPath = database_path('migrations');
        $files = glob($migrationPath . '/*_create_jobs_table.php');

        $this->assertNotEmpty(
            $files,
            "A migration creating the 'jobs' table must exist in database/migrations."
        );
    }

    // -------------------------------------------------------------------------
    // TC-09: failed_jobs table migration file exists
    // -------------------------------------------------------------------------

    public function test_imp008_tc09_failed_jobs_table_migration_file_exists(): void
    {
        $migrationPath = database_path('migrations');
        $files = glob($migrationPath . '/*_create_failed_jobs_table.php');

        $this->assertNotEmpty(
            $files,
            "A migration creating the 'failed_jobs' table must exist in database/migrations."
        );
    }

    // -------------------------------------------------------------------------
    // TC-10: jobs and failed_jobs tables exist after migrations (schema check)
    // -------------------------------------------------------------------------

    public function test_imp008_tc10_jobs_and_failed_jobs_tables_exist_in_schema(): void
    {
        $this->assertTrue(
            Schema::hasTable('jobs'),
            "The 'jobs' table must exist in the database schema."
        );

        $this->assertTrue(
            Schema::hasTable('failed_jobs'),
            "The 'failed_jobs' table must exist in the database schema."
        );
    }
}
