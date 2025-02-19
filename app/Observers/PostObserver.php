<?php

namespace App\Observers;

use App\Models\Post;
use App\Jobs\CreatePostNotifications;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        // Dispatch the job to create notifications
        CreatePostNotifications::dispatch($post);
    }
}
