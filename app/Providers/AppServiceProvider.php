<?php

namespace App\Providers;

use App\Events\UserRegistered;
use App\Events\ContestCreated;
use App\Listeners\SendWelcomeNotifications;
use App\Listeners\NotifyUsersOfNewContest;
use Illuminate\Support\Facades\Event;
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
        // Registrazione eventi e listener
        Event::listen(
            UserRegistered::class,
            SendWelcomeNotifications::class,
        );

        Event::listen(
            ContestCreated::class,
            NotifyUsersOfNewContest::class,
        );
    }
}
