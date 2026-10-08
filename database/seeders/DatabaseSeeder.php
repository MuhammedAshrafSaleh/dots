<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the admin account and the design
     * content/images (so the site mirrors the design page by page).
     * LargeCatalogSeeder and DemoSeeder are run explicitly, never here.
     */
    public function run(): void
    {
        $this->call([
            RolesAndAdminSeeder::class,
            GovernoratesSeeder::class,
            AttributesSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            CommerceSeeder::class,
            SiteSeeder::class,
        ]);

        // Demo customers/orders/leads only on local/staging — never in production.
        if (app()->environment(['local', 'staging'])) {
            $this->call(DemoSeeder::class);
        }
    }
}
