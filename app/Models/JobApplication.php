<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Job;

class JobApplication extends Model
{
    protected $table = 'eq_jobs_app';
    
    protected $fillable = [
        'job_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'country',
        'company',
        'job_title',
        'cv_file_path', // Add this line
        'status'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}