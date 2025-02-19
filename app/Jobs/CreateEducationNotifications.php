<?php

namespace App\Jobs;

use App\Models\EducationContent;
use App\Models\User;
use App\Models\EqNotification;
use App\Mail\EducationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateEducationNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $educationModel;

    /**
     * Create a new job instance.
     */
    public function __construct(EducationContent $education)
    {
        $this->educationModel = $education;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Get all users except the content creator
        $users = User::where('id', '!=', $this->educationModel->user_id)->get();

        // Create notifications in chunks to avoid memory issues
        foreach ($users->chunk(100) as $chunk) {
            $notifications = $chunk->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'by_user' => $this->educationModel->user_id,
                    'foreign_id' => $this->educationModel->id,
                    'notif_type' => 'education',
                    'content' => 'New Educational Content: ' . $this->educationModel->title,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            EqNotification::insert($notifications);

            // Send emails to each user in the chunk
            foreach ($chunk as $user) {
                $emailData = [
                    'type' => 'new_education',
                    'recipient_name' => $user->name,
                    'education_title' => $this->educationModel->title,
                    'education_description' => Str::limit($this->educationModel->short_description, 100),
                    'education_id' => $this->educationModel->id,
                    'author_name' => $this->educationModel->user->name
                ];

                Mail::to($user->email)->queue(new EducationNotification($emailData));
            }
        }
    }
}
