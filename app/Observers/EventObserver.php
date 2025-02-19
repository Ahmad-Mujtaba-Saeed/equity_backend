<?php

namespace App\Observers;

use App\Models\Event;
use App\Jobs\CreateEventNotifications;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        Log::info('EventObserver: Event created', ['event_id' => $event->id, 'is_active' => $event->is_active]);
        
        // Make sure the event is loaded with its relationships
        $event->load('creator');
        
        // Only dispatch notifications if the event is active
        if ($event->is_active) {
            Log::info('EventObserver: Dispatching notifications for event', ['event_id' => $event->id]);
            CreateEventNotifications::dispatch($event);
        } else {
            Log::info('EventObserver: Event is not active, skipping notifications', ['event_id' => $event->id]);
        }
    }
}
