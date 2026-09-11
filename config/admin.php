<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder to create the first account that can reach the
    | admin panel. Set these in .env before seeding a production install; the
    | seeder never overwrites the password of an account that already exists.
    |
    */

    'seed_name' => env('ADMIN_SEED_NAME', 'Panchforon Admin'),
    'seed_email' => env('ADMIN_SEED_EMAIL', 'admin@panchforon.test'),
    'seed_password' => env('ADMIN_SEED_PASSWORD', 'password'),

];
