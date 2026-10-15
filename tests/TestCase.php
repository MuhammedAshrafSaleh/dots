<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // --- Safety guard: abort before any database access ---

        // 1. Cached config bakes in the dev DB and ignores phpunit.xml env entries.
        if (file_exists(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new \RuntimeException(
                "SAFETY ABORT: bootstrap/cache/config.php exists.\n".
                "Run `php artisan config:clear` then retry.\n".
                'A cached config ignores phpunit.xml env entries and may target the wrong database.'
            );
        }

        // 2. APP_ENV must be 'testing' (checked via raw env before the app boots).
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
        if ($appEnv !== 'testing') {
            throw new \RuntimeException(
                "SAFETY ABORT: APP_ENV is '{$appEnv}', expected 'testing'."
            );
        }

        // 3. DB_DATABASE must be dots_testing (parallel: dots_testing_test_N).
        $dbName = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
        if (! preg_match('/^dots_testing(_test_\d+)?$/', $dbName)) {
            throw new \RuntimeException(
                "SAFETY ABORT: DB_DATABASE env is '{$dbName}', expected 'dots_testing'.\n".
                'Tests must not touch the dev database.'
            );
        }

        parent::setUp();
    }

    protected function setUpTraits(): array
    {
        // Post-boot guard: app is created so config() is available, but no DB trait
        // (RefreshDatabase / DatabaseTruncation) has run yet.
        $connection = config('database.default');
        $cfgDb = config("database.connections.{$connection}.database", '');
        if (! preg_match('/^dots_testing(_test_\d+)?$/', (string) $cfgDb)) {
            throw new \RuntimeException(
                "SAFETY ABORT: resolved database config is '{$cfgDb}', expected 'dots_testing'.\n".
                'Refusing to run database operations on the wrong database.'
            );
        }

        return parent::setUpTraits();
    }
}
