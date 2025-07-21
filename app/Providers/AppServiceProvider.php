<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        if (env('APP_ENV') !== 'local') {
        URL::forceScheme('https');
        }

        // Definir permisos
        Gate::define('is-admin', function ($user) {
            return $user->rol === 'admin' || $user->email === 'admin@gmail.com';
        });

        Gate::define('is-profesor', function ($user) {
            return $user->rol === 'profesor';
        });

        // Si quieres pasar variables a las vistas, usa view()->composer en un closure diferido
        $this->app->booted(function () {
            view()->composer('*', function ($view) {
                $user = Auth::user();
                $rol = null;

                if ($user) {
                    if ($user->email === 'admin@gmail.com') {
                        $rol = 'admin';
                        config(['adminlte.dashboard_url' => 'admin.dashboard']);
                    } elseif ($user->rol === 'profesor') {
                        $rol = 'profesor';
                        config(['adminlte.dashboard_url' => 'profesor.dashboard']);
                    }
                }

                $view->with('rol_moodle', $rol);
            });
        });
    }
}
