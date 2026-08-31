<?php

namespace App\Providers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\ClassPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registered explicitly: policy auto-discovery would look for
        // SchoolClassPolicy and UserPolicy, which is not what these are called.
        Gate::policy(SchoolClass::class, ClassPolicy::class);
        Gate::policy(User::class, StudentPolicy::class);
    }
}
