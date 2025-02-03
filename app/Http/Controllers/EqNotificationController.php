<?php

namespace App\Http\Controllers;

use App\Models\EqNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EqNotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        // First, get message notifications grouped by conversation
        $messageNotifications = EqNotification::where('user_id', Auth::id())
            ->where('notif_type', 'message')
            ->where('is_read', false)
            ->select([
                'foreign_id',
                DB::raw('COUNT(*) as message_count'),
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(user_id) as user_id'),
                DB::raw('MAX(by_user) as by_user'),
                DB::raw('MAX(notif_type) as notif_type'),
                DB::raw('MAX(content) as content'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('MAX(is_read) as is_read')
            ])
            ->groupBy('foreign_id');

        // Then get all other notifications
        $otherNotifications = EqNotification::where('user_id', Auth::id())
            ->where('notif_type', '!=', 'message')
            ->select([
                'foreign_id',
                DB::raw('1 as message_count'),
                'id',
                'user_id',
                'by_user',
                'notif_type',
                'content',
                'created_at',
                'is_read'
            ]);

        // Combine queries and add relationships
        $query = $otherNotifications->union($messageNotifications)
            ->orderBy('created_at', 'desc');

        // Filter by read/unread status if specified
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $notifications = $query->paginate(20);

        // Load relationships after pagination
        $notifications->load('user');

        // Modify the content for message notifications to include count
        $notifications->through(function ($notification) {
            if ($notification->notif_type === 'message' && $notification->message_count > 1) {
                $notification->content = "You have {$notification->message_count} new messages in this conversation";
            }
            return $notification;
        });

        // Calculate total unread count
        $totalUnread = EqNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->where(function ($query) {
                $query->where('notif_type', '!=', 'message')
                    ->orWhereIn('foreign_id', function ($subquery) {
                        $subquery->select('foreign_id')
                            ->from('eq_notifications')
                            ->where('user_id', Auth::id())
                            ->where('notif_type', 'message')
                            ->where('is_read', false)
                            ->groupBy('foreign_id');
                    });
            })
            ->count();

        return response()->json([
            'data' => $notifications->items(),
            'total_unread' => $totalUnread,
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'last_page' => $notifications->lastPage(),
            'total' => $notifications->total()
        ]);
    }

    /**
     * Create a new notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'by_user' => 'exists:users,id',
            'foreign_id' => 'required|integer',
            'notif_type' => 'required|string',
            'content' => 'nullable|string'
        ]);

        $notification = EqNotification::create($validated);
        
        return response()->json($notification, 201);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $notification = EqNotification::where('user_id', Auth::id())
            ->findOrFail($id);
            
        $notification->markAsRead();
        
        return response()->json($notification);
    }

    /**
     * Mark all notifications as read for the authenticated user
     */
    public function markAllAsRead()
    {
        EqNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get unread notification count for the authenticated user
     */
    public function getUnreadCount()
    {
        $count = EqNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
            
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $notification = EqNotification::where('user_id', Auth::id())
            ->findOrFail($id);
            
        $notification->delete();
        
        return response()->json(null, 204);
    }

    /**
     * Delete all notifications for the authenticated user
     */
    public function destroyAll()
    {
        EqNotification::where('user_id', Auth::id())->delete();
        
        return response()->json(['message' => 'All notifications deleted']);
    }
}
