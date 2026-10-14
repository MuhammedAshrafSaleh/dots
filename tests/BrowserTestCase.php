<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * Base class for Pest Browser (Playwright) tests.
 *
 * Spatie permission lookup tables are reference-data that never change between
 * tests — every test seeds the same set from config('permissions'). Excluding
 * them from DatabaseTruncation means adminUser() calls findOrCreate() on
 * already-existing rows (no INSERT) and avoids the InnoDB deadlock that occurs
 * when the Pest Browser HTTP-server process holds a snapshot read on these
 * tables while the test process tries to truncate and re-insert them.
 */
abstract class BrowserTestCase extends TestCase
{
    use DatabaseTruncation;

    protected array $exceptTables = ['permissions', 'roles', 'role_has_permissions'];
}
