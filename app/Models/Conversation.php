<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_id',
        'last_message',
        'last_message_time'
    ];

    protected $casts = [
        'last_message_time' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
