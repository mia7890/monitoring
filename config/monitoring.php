<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator Access Key
    |--------------------------------------------------------------------------
    |
    | The shared secret used to authenticate the administrator workspace. It
    | must be explicitly declared in the application environment; this
    | application intentionally fails closed (no default value) so that a
    | missing key cannot silently grant administrator access.
    |
    */

    'admin_key' => env('MONITORING_ADMIN_KEY'),

];