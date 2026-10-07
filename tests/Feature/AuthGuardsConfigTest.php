<?php

use Illuminate\Support\Facades\Schema;

it('configures the admin and customer guards on the session driver', function () {
    expect(config('auth.guards.admin'))->toMatchArray(['driver' => 'session', 'provider' => 'users']);
    expect(config('auth.guards.customer'))->toMatchArray(['driver' => 'session', 'provider' => 'customers']);
});

it('points the customer provider at the Customer model', function () {
    expect(config('auth.providers.customers.driver'))->toBe('eloquent');
    expect(config('auth.providers.customers.model'))->toBe('App\\Models\\Customer');
});

it('uses the database session driver by default so sessions can be revoked', function () {
    // The test suite forces SESSION_DRIVER=array, so assert the config default
    // fallback is `database` and that the sessions table ships in migrations.
    expect(file_get_contents(config_path('session.php')))
        ->toContain("env('SESSION_DRIVER', 'database')");

    expect(Schema::hasTable('sessions'))->toBeTrue();
});
