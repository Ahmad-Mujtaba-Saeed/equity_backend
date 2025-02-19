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
        Log::info('AppServiceProvider: Registering observers');
        Job::observe(JobObserver::class);
        Event::observe(EventObserver::class);
        EducationContent::observe(EducationContentObserver::class);
    }
}
