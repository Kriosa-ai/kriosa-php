<?php
// examples/laravel.php - How to integrate with Laravel
/*
1. Add to .env:
   KRIOSA_API_KEY=sk_your_key

2. Create middleware:
   php artisan make:middleware KriosaSecurity

3. In app/Http/Middleware/KriosaSecurity.php:
   use Kriosa\Kriosa;
   
   public function handle($request, $next)
   {
       if (!kriosa_protect(env('KRIOSA_API_KEY'))) {
           return response('Request blocked', 403);
       }
       return $next($request);
   }

4. Register in app/Http/Kernel.php:
   protected $middleware = [
       \App\Http\Middleware\KriosaSecurity::class,
   ];
*/