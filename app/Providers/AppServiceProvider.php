<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\RetoCompletado;
use App\Models\LikePublicacion;
use App\Observers\RetoCompletadoObserver;
use App\Observers\LikePublicacionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registrar los observers
        // A partir de aquí Laravel los escucha automáticamente
        RetoCompletado::observe(RetoCompletadoObserver::class);
        LikePublicacion::observe(LikePublicacionObserver::class);
        // Limiter para evitar abuso de la API
        RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by(
        $request->user()?->id ?: $request->ip()
        );
        });
    }
}