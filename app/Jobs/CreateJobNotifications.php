<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\User;
use App\Models\EqNotification;
use App\Mail\JobNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateJobNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobModel;

    /**
     * Create a new job instance.
     */
    public function __construct(Job $job)
    {
        $this->jobModel = $job;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Get all users except the job creator
        $users = User::where('id', '!=', $this->jobModel->user_id)->get();

        // Create notifications in chunks to avoid memory issues
        foreach ($users->chunk(100) as $chunk) {
            $notifications = $chunk->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'by_user' => $this->jobModel->user_id,
                    'foreign_id' => $this->jobModel->id,
                    'notif_type' => 'job',
                    'content' => 'New Job Opportunity Posted',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            EqNotification::insert($notifications);

            // Send emails to each user in the chunk
            foreach ($chunk as $user) {
                $emailData = [
                    'type' => 'new_job',
                    'recipient_name' => $user->name,
                    'job_title' => $this->jobModel->title,
                    'job_description' => Str::limit($this->jobModel->short_description, 100),
                    'job_id' => $this->jobModel->id,
                    'company_name' => $this->jobModel->user->name
                ];

                Mail::to($user->email)->queue(new JobNotification($emailData));
            }
        }
    }
}
