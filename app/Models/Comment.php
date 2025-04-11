<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'reply_to',
        'media',
        'post_id',
        'content',
        'parent_id'
    ];
    protected $appends = ['isLiked'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'comment_id', 'id')
            ->where('type', 'comment');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    /**
     * Check if the current user has liked this comment
     */
    public function isLikedByCurrentUser()
    {
        if (!auth()->check()) {
            return false;
        }
        
        return $this->likes()
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Get the isLiked attribute
     */
    protected function getIsLikedAttribute(): bool
    {
        return $this->isLikedByCurrentUser();
    }
}
