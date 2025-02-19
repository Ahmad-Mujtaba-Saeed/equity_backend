<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'short_description',
        'description',
        'main_image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true // Set default value to true
    ];

    protected $with = ['user'];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($job) {
            Log::info('Job Model: Creating event triggered', [
                'job_id' => $job->id,
                'is_active' => $job->is_active
            ]);
            // Set is_active to true if it's not set
            if ($job->is_active === null) {
                $job->is_active = true;
            }
        });

        static::created(function ($job) {
            Log::info('Job Model: Created event triggered', [
                'job_id' => $job->id,
                'is_active' => $job->is_active
            ]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
