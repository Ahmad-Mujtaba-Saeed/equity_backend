<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Post;
use App\Models\User;
use App\Models\Event;
use App\Models\Job;
use App\Models\EducationContent;
use App\Models\EqNotification;
use App\Mail\PostNotification;
use App\Mail\EventNotification;
use App\Mail\JobNotification;
use App\Mail\EducationNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Broadcast;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class NotificationProcessor
{
    private function broadcastNotification($notification)
    {
        // Load the user data for the notification sender
        $sender = User::find($notification->by_user);
        
        // Prepare notification data
        $notificationData = [
            'id' => $notification->id,
            'foreign_id' => $notification->foreign_id,
            'recipient_id' => $notification->user_id,
            'type' => $notification->notif_type,
            'is_read' => $notification->is_read,
            'sender' => [
                'id' => $notification->by_user,
                'name' => $sender->name,
                'profile_image' => $sender->profile_image
            ],
            'message' => $notification->content,
            'created_at' => $notification->created_at
        ];

        // Broadcast the notification event
        Broadcast::event(new NewNotification($notificationData));
    }

    private function createAndBroadcastNotifications($notifications)
    {
        // Insert notifications in chunks
        $createdNotifications = [];
        foreach (array_chunk($notifications, 100) as $chunk) {
            $ids = EqNotification::insert($chunk);
            // Get the created notifications
            $created = EqNotification::where('created_at', '>=', now()->subSeconds(5))
                                   ->whereIn('user_id', array_column($chunk, 'user_id'))
                                   ->whereIn('by_user', array_column($chunk, 'by_user'))
                                   ->whereIn('foreign_id', array_column($chunk, 'foreign_id'))
                                   ->get();
            $createdNotifications = array_merge($createdNotifications, $created->all());
        }

        // Broadcast each notification
        foreach ($createdNotifications as $notification) {
            $this->broadcastNotification($notification);
        }

        return $createdNotifications;
    }

    public function process()
    {
        try {
            $this->processNewPosts();
            $this->processNewEvents();
            $this->processNewJobs();
            $this->processNewEducation();
            echo "All notifications processed successfully.\n";
        } catch (\Exception $e) {
            echo "Error processing notifications: " . $e->getMessage() . "\n";
        }
    }

    private function processNewPosts()
    {
        // Get posts created in the last 2 minutes
        $posts = Post::where('created_at', '>=', Carbon::now()->subMinutes(2))->get();
        
        foreach ($posts as $post) {
            try {
                // Get all users except the post creator
                $users = User::where('id', '!=', $post->user_id)->get();

                foreach ($users->chunk(100) as $chunk) {
                    $notifications = $chunk->map(function ($user) use ($post) {
                        return [
                            'user_id' => $user->id,
                            'by_user' => $post->user_id,
                            'foreign_id' => $post->id,
                            'notif_type' => 'post',
                            'content' => 'New Post Created',
                            'is_read' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    })->toArray();

                    // Create and broadcast notifications
                    $this->createAndBroadcastNotifications($notifications);

                    foreach ($chunk as $user) {
                        $emailData = [
                            'type' => 'new_post',
                            'recipient_name' => $user->name,
                            'post_title' => Str::limit($post->title, 50),
                            'post_id' => $post->id,
                            'author_name' => $post->user->name
                        ];

                        Mail::to($user->email)->send(new PostNotification($emailData));
                    }
                }
                echo "Processed notifications for post ID: {$post->id}\n";
            } catch (\Exception $e) {
                echo "Error processing post {$post->id}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function processNewEvents()
    {
        // Get events created in the last 2 minutes
        $events = Event::where('created_at', '>=', Carbon::now()->subMinutes(2))->get();
        
        foreach ($events as $event) {
            try {
                $users = User::where('id', '!=', $event->created_by)->get();

                foreach ($users->chunk(100) as $chunk) {
                    $notifications = $chunk->map(function ($user) use ($event) {
                        return [
                            'user_id' => $user->id,
                            'by_user' => $event->created_by,
                            'foreign_id' => $event->id,
                            'notif_type' => 'event',
                            'content' => 'New Event Added: ' . $event->title,
                            'is_read' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    })->toArray();

                    // Create and broadcast notifications
                    $this->createAndBroadcastNotifications($notifications);

                    foreach ($chunk as $user) {
                        $emailData = [
                            'type' => 'new_event',
                            'recipient_name' => $user->name,
                            'event_title' => $event->title,
                            'event_subtitle' => $event->subtitle,
                            'event_date' => $event->event_date->format('F j, Y'),
                            'event_time' => $event->start_time->format('g:i A'),
                            'event_id' => $event->id,
                            'event_description' => Str::limit($event->description, 100)
                        ];

                        Mail::to($user->email)->send(new EventNotification($emailData));
                    }
                }
                echo "Processed notifications for event ID: {$event->id}\n";
            } catch (\Exception $e) {
                echo "Error processing event {$event->id}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function processNewJobs()
    {
        // Get jobs created in the last 2 minutes
        $jobs = Job::where('created_at', '>=', Carbon::now()->subMinutes(2))->get();
        
        foreach ($jobs as $job) {
            try {
                $users = User::where('id', '!=', $job->user_id)->get();

                foreach ($users->chunk(100) as $chunk) {
                    $notifications = $chunk->map(function ($user) use ($job) {
                        return [
                            'user_id' => $user->id,
                            'by_user' => $job->user_id,
                            'foreign_id' => $job->id,
                            'notif_type' => 'job',
                            'content' => 'New Job Opportunity Posted',
                            'is_read' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    })->toArray();

                    // Create and broadcast notifications
                    $this->createAndBroadcastNotifications($notifications);

                    foreach ($chunk as $user) {
                        $emailData = [
                            'type' => 'new_job',
                            'recipient_name' => $user->name,
                            'job_title' => $job->title,
                            'job_description' => Str::limit($job->short_description, 100),
                            'job_id' => $job->id,
                            'company_name' => $job->user->name
                        ];

                        Mail::to($user->email)->send(new JobNotification($emailData));
                    }
                }
                echo "Processed notifications for job ID: {$job->id}\n";
            } catch (\Exception $e) {
                echo "Error processing job {$job->id}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function processNewEducation()
    {
        // Get education content created in the last 2 minutes
        $educationContents = EducationContent::where('created_at', '>=', Carbon::now()->subMinutes(2))->get();
        
        foreach ($educationContents as $education) {
            try {
                $users = User::where('id', '!=', $education->user_id)->get();

                foreach ($users->chunk(100) as $chunk) {
                    $notifications = $chunk->map(function ($user) use ($education) {
                        return [
                            'user_id' => $user->id,
                            'by_user' => $education->user_id,
                            'foreign_id' => $education->id,
                            'notif_type' => 'education',
                            'content' => 'New Educational Content: ' . $education->title,
                            'is_read' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    })->toArray();

                    // Create and broadcast notifications
                    $this->createAndBroadcastNotifications($notifications);

                    foreach ($chunk as $user) {
                        $emailData = [
                            'type' => 'new_education',
                            'recipient_name' => $user->name,
                            'education_title' => $education->title,
                            'education_description' => Str::limit($education->short_description, 100),
                            'education_id' => $education->id,
                            'author_name' => $education->user->name
                        ];

                        Mail::to($user->email)->send(new EducationNotification($emailData));
                    }
                }
                echo "Processed notifications for education content ID: {$education->id}\n";
            } catch (\Exception $e) {
                echo "Error processing education content {$education->id}: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Run the processor
$processor = new NotificationProcessor();
$processor->process();
