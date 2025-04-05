<?php

namespace App\Observers;

use App\Models\EducationContent;
use App\Jobs\CreateEducationNotifications;
use Illuminate\Support\Facades\Log;

class EducationContentObserver
{
    /**
     * Handle the EducationContent "created" event.
     */
    // public function created(EducationContent $education): void
    // {
    //     Log::info('EducationContentObserver: Education content created', ['education_id' => $education->id]);
        
    //     // Make sure the education content is loaded with its relationships
    //     $education->load('user');
        
    //     Log::info('EducationContentObserver: Dispatching notifications for education content', ['education_id' => $education->id]);
    //     CreateEducationNotifications::dispatch($education);
    // }
}
