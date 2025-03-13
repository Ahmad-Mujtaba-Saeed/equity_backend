<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function getUnreadNotifications()
    {
        $userId = auth()->id();
        
        // Get unread messages grouped by conversation
        $unreadNotifications = Message::where('is_read', false)
            ->whereHas('conversation', function($query) use ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhere('recipient_id', $userId);
                });
            })
            ->where('sender_id', '!=', $userId)
            ->with(['sender:id,name,profile_image', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($message) use ($userId) {
                // Get the other user (sender) details
                return [
                    'id' => $message->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'profile_image' => $message->sender->profile_image
                    ],
                    'message' => $message->content,
                    'conversation_id' => $message->conversation_id,
                    'created_at' => $message->created_at,
                    'type' => 'message'
                ];
            })
            ->take(5); // Limit to 5 most recent notifications

        // Get total count of unread notifications
        $totalUnread = Message::where('is_read', false)
            ->whereHas('conversation', function($query) use ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhere('recipient_id', $userId);
                });
            })
            ->where('sender_id', '!=', $userId)
            ->count();

        return response()->json([
            'notifications' => $unreadNotifications,
            'total_unread' => $totalUnread
        ]);
    }
}
