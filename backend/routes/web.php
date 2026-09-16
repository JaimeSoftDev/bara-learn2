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
$serveSpa = function () {
    $spaIndex = public_path('spa-index.html');

    abort_unless(file_exists($spaIndex), 404);

    return Response::make(file_get_contents($spaIndex), 200, ['Content-Type' => 'text/html']);
};

// Named so Laravel's auth middleware can resolve route('login') instead of
// throwing when it tries to redirect a request that isn't expecting JSON
// (e.g. a browser navigating straight to a protected URL with an expired
// session). Vue Router renders the actual login form client-side.
Route::get('/login', $serveSpa)->name('login');

Route::fallback($serveSpa);
