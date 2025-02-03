<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Models\EqNotification;
use App\Mail\PostNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatePostNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    /**
     * Create a new job instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Get all users except the post creator
        $users = User::where('id', '!=', $this->post->user_id)->get();

        // Create notifications in chunks to avoid memory issues
        foreach ($users->chunk(100) as $chunk) {
            $notifications = $chunk->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'by_user' => $this->post->user_id,
                    'foreign_id' => $this->post->id,
                    'notif_type' => 'post',
                    'content' => 'New Post Created',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            EqNotification::insert($notifications);

            // Send emails to each user in the chunk
            foreach ($chunk as $user) {
                $emailData = [
                    'type' => 'new_post',
                    'recipient_name' => $user->name,
                    'post_title' => Str::limit($this->post->title, 100),
                    'post_id' => $this->post->id,
                    'actor_name' => $this->post->user->name
                ];

                Mail::to($user->email)->queue(new PostNotification($emailData));
            }
        }
    }
}
