<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Models\EqNotification;
use App\Mail\EventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateEventNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventModel;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->eventModel = $event;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Get all users except the event creator
        $users = User::where('id', '!=', $this->eventModel->created_by)->get();

        // Create notifications in chunks to avoid memory issues
        foreach ($users->chunk(100) as $chunk) {
            $notifications = $chunk->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'by_user' => $this->eventModel->created_by,
                    'foreign_id' => $this->eventModel->id,
                    'notif_type' => 'event',
                    'content' => 'New Event Added: ' . $this->eventModel->title,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            EqNotification::insert($notifications);

            // Send emails to each user in the chunk
            foreach ($chunk as $user) {
                $emailData = [
                    'type' => 'new_event',
                    'recipient_name' => $user->name,
                    'event_title' => $this->eventModel->title,
                    'event_subtitle' => $this->eventModel->subtitle,
                    'event_date' => $this->eventModel->event_date->format('F j, Y'),
                    'event_time' => $this->eventModel->start_time->format('g:i A'),
                    'event_id' => $this->eventModel->id,
                    'event_description' => Str::limit($this->eventModel->description, 100)
                ];

                Mail::to($user->email)->queue(new EventNotification($emailData));
            }
        }
    }
}
