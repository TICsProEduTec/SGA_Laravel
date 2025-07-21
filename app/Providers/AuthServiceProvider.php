<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::define('is-admin', function ($user) {
            return $user->rol === 'admin';
        });

        Gate::define('is-profesor', function ($user) {
            return $user->rol === 'profesor';
        });
    }
}
