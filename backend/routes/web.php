<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA catch-all
|--------------------------------------------------------------------------
|
| The compiled Vue SPA is deployed as static files inside public/ (see
| scripts/build-for-hostinger.sh). Every request that isn't a real route
| (an /api/* endpoint, /sanctum/csrf-cookie, /up, or an existing static
| asset such as /assets/*.js) falls back to the SPA's index.html so that
| Vue Router's history mode can handle client-side navigation.
*/
Route::fallback(function () {
    $spaIndex = public_path('spa-index.html');

    abort_unless(file_exists($spaIndex), 404);

    return Response::make(file_get_contents($spaIndex), 200, ['Content-Type' => 'text/html']);
});
