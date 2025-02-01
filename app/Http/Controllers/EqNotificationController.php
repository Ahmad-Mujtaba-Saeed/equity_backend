<?php

namespace App\Http\Controllers;

use App\Models\EqNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EqNotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $query = EqNotification::where('user_id', Auth::id())
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by read/unread status if specified
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $notifications = $query->paginate(20);
        
        return response()->json($notifications);
    }

    /**
     * Create a new notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
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
            
        return response()->json(['message' => 'All notifications marked as read']);
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
