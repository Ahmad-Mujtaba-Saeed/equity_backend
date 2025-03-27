<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Job;
use App\Models\Event;
use App\Models\EducationContent;
use App\Observers\JobObserver;
use App\Observers\EventObserver;
use App\Observers\EducationContentObserver;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as AppAuth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bind Firebase Auth service to the application container
        $this->app->singleton(AppAuth::class, function ($app) {
            return (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createAuth();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::info('AppServiceProvider: Registering observers');
        Job::observe(JobObserver::class);
        Event::observe(EventObserver::class);
        EducationContent::observe(EducationContentObserver::class);
    }
}
