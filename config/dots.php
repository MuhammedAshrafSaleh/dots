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

    // Seeded admin account (RolesAndAdminSeeder).
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@dots.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
