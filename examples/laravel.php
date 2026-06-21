<?php
/**
 * Kriosa Laravel Integration
 * 
 * Full guide: https://kriosa.com/documentation.php/laravel
 */

/*
|--------------------------------------------------------------------------
| STEP 1 — Install via Composer
|--------------------------------------------------------------------------
|
|   composer require kriosa-ai/kriosa-php
|
*/

/*
|--------------------------------------------------------------------------
| STEP 2 — Add to your .env file
|--------------------------------------------------------------------------
|
|   KRIOSA_API_KEY=sk_your_api_key_here
|   KRIOSA_TIMEOUT=5
|   KRIOSA_DEBUG=false
|   KRIOSA_BADGE=true
|
*/

/*
|--------------------------------------------------------------------------
| STEP 3 — Create config/kriosa.php
|--------------------------------------------------------------------------
*/

// config/kriosa.php
return [
    'api_key' => env('KRIOSA_API_KEY'),
    'timeout' => env('KRIOSA_TIMEOUT', 5),
    'debug'   => env('KRIOSA_DEBUG', false),
];

/*
|--------------------------------------------------------------------------
| STEP 4 — Create the Middleware
|--------------------------------------------------------------------------
|
|   php artisan make:middleware KriosaSecurity
|
| Then replace the contents with:
*/

// namespace App\Http\Middleware; 

use Closure;
use Kriosa;
use Illuminate\Http\Request;

class KriosaSecurity
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = config('kriosa.api_key');

        // Skip if no API key configured
        if (!$apiKey) {
            return $next($request);
        }

        try {
            $kriosa = new Kriosa($apiKey, [
                'timeout' => config('kriosa.timeout', 5),
                'debug'   => config('kriosa.debug', false),
            ]);

            if (!$kriosa->protect()) {
                return response('Access denied', 403);
            }

        } catch (\Exception $e) {
            // Fail open — don't block users if Kriosa is unreachable
            report($e);
        }

        return $next($request);
    }
}

/*
|--------------------------------------------------------------------------
| STEP 5 — Register the Middleware
|--------------------------------------------------------------------------
|
| In app/Http/Kernel.php, add to $middleware array:
|
|   protected $middleware = [
|       // ... existing middleware
|       \App\Http\Middleware\KriosaSecurity::class,
|   ];
|
| Or apply to specific routes only in routes/web.php:
|
|   Route::middleware(['kriosa'])->group(function () {
|       Route::get('/dashboard', [DashboardController::class, 'index']);
|   });
|
*/

/*
|--------------------------------------------------------------------------
| STEP 6 — Test it
|--------------------------------------------------------------------------
|
|   php artisan serve
|
| Then visit your app. You should see the "Protected by Kriosa" badge
| in the bottom-right corner of your pages.
|
| To test a blocked request, try:
|   curl "http://localhost:8000/?q=<script>alert(1)</script>"
|
*/