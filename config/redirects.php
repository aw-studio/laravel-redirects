<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redirect Model
    |--------------------------------------------------------------------------
    |
    | This model is responsible for handling redirects stored in the databse.
    |
    */

    'model' => \AwStudio\Redirects\Models\Redirect::class,

    /*
    |--------------------------------------------------------------------------
    | Preconfigured redirects
    |--------------------------------------------------------------------------
    |
    | You may also configure static redirects by specifying them in this array.
    | Laravel's route parameters can be applied.
    |
    */
    'redirects' => [
        // '/old' => '/new',
        // 'blog/{url}' => 'news/{url}',
    ],
];
