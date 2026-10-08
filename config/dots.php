<?php

/*
|--------------------------------------------------------------------------
| DOTS application settings
|--------------------------------------------------------------------------
|
| Project-specific config. Keeping env() reads here (not in seeders/services)
| means values survive `config:cache`.
|
*/

return [

    // Seeded admin account (RolesAndAdminSeeder). No password default — the
    // seeder requires ADMIN_PASSWORD in production and only falls back to a
    // throwaway local password outside production.
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@dots.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
