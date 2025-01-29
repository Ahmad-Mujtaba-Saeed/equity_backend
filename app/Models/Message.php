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
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    protected $with = ['sender'];

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
}
