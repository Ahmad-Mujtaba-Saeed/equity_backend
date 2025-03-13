<?php

namespace App\Observers;

use App\Models\Job;
use App\Jobs\CreateJobNotifications;
use Illuminate\Support\Facades\Log;

class JobObserver
{
    /**
     * Handle the Job "created" event.
     */
    public function created(Job $job): void
    {
        Log::info('JobObserver: Job created', ['job_id' => $job->id, 'is_active' => $job->is_active]);
        
        // Make sure the job is loaded with its relationships
        $job->load('user');
        
        // Only dispatch notifications if the job is active
        if ($job->is_active) {
            Log::info('JobObserver: Dispatching notifications for job', ['job_id' => $job->id]);
            CreateJobNotifications::dispatch($job);
        } else {
            Log::info('JobObserver: Job is not active, skipping notifications', ['job_id' => $job->id]);
        }
    }
}
