<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings cache
    |--------------------------------------------------------------------------
    |
    | The settings table is read on nearly every request, so the whole table is
    | cached under this key and only invalidated on write. This deploys to
    | shared hosting with the database cache store and no queue worker, so the
    | flush happens synchronously inside the same request that saves.
    |
    */

    'cache_key' => 'panchforon.settings',

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Used when a key has no row yet, so the application behaves sensibly
    | before an admin has ever opened the settings screen.
    |
    */

    'defaults' => [
        'site_name' => 'Panchforon',
        'contact_email' => 'hello@panchforon.com',
        'registration_open' => true,
        'submissions_open' => true,
        'mealdb_import_limit' => 300,
        'mealdb_import_areas' => ['Indian', 'Italian', 'Mexican', 'British', 'Chinese', 'American'],
    ],

];
