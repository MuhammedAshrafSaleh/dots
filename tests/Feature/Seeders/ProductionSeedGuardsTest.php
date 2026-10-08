<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\LargeCatalogSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/** Run a seeder directly (no db:seed command, so no production confirm prompt). */
function runSeeder(string $class): void
{
    $command = new Command;
    $command->setLaravel(app());
    $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

    /** @var Seeder $seeder */
    $seeder = app($class);
    $seeder->setContainer(app())->setCommand($command)->__invoke();
}

function pretendProduction(): void
{
    app()->detectEnvironment(fn () => 'production');
    expect(app()->isProduction())->toBeTrue();
}

it('does not run DemoSeeder in production', function () {
    pretendProduction();

    runSeeder(DemoSeeder::class);

    expect(Customer::count())->toBe(0);
});

it('does not run LargeCatalogSeeder in production', function () {
    pretendProduction();

    runSeeder(LargeCatalogSeeder::class);

    expect(Product::count())->toBe(0);
});

it('fails loudly when seeding the admin in production without ADMIN_PASSWORD', function () {
    pretendProduction();
    config(['dots.admin.password' => null]);

    runSeeder(RolesAndAdminSeeder::class);
})->throws(RuntimeException::class, 'ADMIN_PASSWORD must be set');

it('seeds the admin in production when ADMIN_PASSWORD is provided', function () {
    pretendProduction();
    config(['dots.admin.password' => 'S3cret-from-env']);

    runSeeder(RolesAndAdminSeeder::class);

    $admin = User::where('email', config('dots.admin.email'))->firstOrFail();
    expect($admin->hasRole('Admin'))->toBeTrue();
});

it('uses the local fallback password outside production', function () {
    config(['dots.admin.password' => null]); // env stays "testing"

    runSeeder(RolesAndAdminSeeder::class);

    expect(User::where('email', config('dots.admin.email'))->exists())->toBeTrue();
});
