<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = [
        'id',
        'user_id',
        'can_create_jobs',
        'can_create_education',
        'can_create_events',
        'can_create_post_category',
        'can_review_job_applications',
        'can_manage_users',
    ];
}
