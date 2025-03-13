<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Conversation;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'is_read',
        'type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    protected $with = ['sender'];

    protected $appends = ['file_url'];

    // Relationship with conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Relationship with sender user
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Get the full URL for file attachments
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url($this->file_path);
        }
        return null;
    }
}
