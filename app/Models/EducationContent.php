<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EducationContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category_id',
        'media',
        'image_path',
        'short_description',
        'description',
        'video_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
