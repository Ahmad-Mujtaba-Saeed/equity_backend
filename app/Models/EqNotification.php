<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EqNotification extends Model
{
    protected $table = 'eq_notifications';

    protected $fillable = [
        'user_id',
        'by_user',
        'foreign_id',
        'notif_type',
        'content',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by_user');
    }

    /**
     * Get the user that created the notification
     */
    // public function byUser(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'by_user');
    // }

    /**
     * Get the related model based on notification type
     */
    public function relatedContent()
    {
        return match($this->notif_type) {
            'post_like', 'post_comment' => $this->belongsTo(Post::class, 'foreign_id'),
            'comment_reply' => $this->belongsTo(Comment::class, 'foreign_id'),
            'event_created' => $this->belongsTo(Event::class, 'foreign_id'),
            'job_posted' => $this->belongsTo(Job::class, 'foreign_id'),
            'education_content' => $this->belongsTo(EducationContent::class, 'foreign_id'),
            'conversation_message' => $this->belongsTo(Message::class, 'foreign_id'),
            'follow' => $this->belongsTo(User::class, 'foreign_id', 'id'),
            default => null
        };
    }

    /**
     * Scope a query to only include unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Mark the notification as read
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }

    /**
     * Get formatted notification message
     */
    public function getFormattedMessage()
    {
        if ($this->content) {
            return $this->content;
        }

        // Default messages based on notification type
        return match($this->notif_type) {
            'post_like' => 'liked your post',
            'post_comment' => 'commented on your post',
            'comment_reply' => 'replied to your comment',
            'event_created' => 'created a new event',
            'job_posted' => 'posted a new job',
            'education_content' => 'added new educational content',
            'conversation_message' => 'sent you a message',
            'follow' => 'started following you',
            default => 'performed an action'
        };
    }
}
