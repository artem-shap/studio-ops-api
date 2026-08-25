<?php

use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\PortalController;
use Illuminate\Support\Facades\Route;

/*
 * The public API, consumed by studio-ops-web server-side and by nothing else.
 *
 * Registered by hand rather than through `artisan install:api`, which would
 * have installed Sanctum. There are no user tokens here: two servers
 * authenticate to each other with a shared secret, so Sanctum would be an
 * unused dependency that a reader would reasonably ask about.
 *
 * Every route below sits behind EnsureStudioApiKey, applied in bootstrap/app.php.
 */

Route::post('/inquiries', [InquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.inquiries.store');

Route::get('/portal/{token}', [PortalController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('api.portal.show');
