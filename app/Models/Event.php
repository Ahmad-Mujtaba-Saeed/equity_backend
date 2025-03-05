<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_image',
        'banner_image',
        'title',
        'description',
        'subtitle',
        'organizer_id',
        'event_date',
        'start_time',
        'end_time',
        'type',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    // Get the creator of the event
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
