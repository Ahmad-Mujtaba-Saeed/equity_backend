<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use App\Events\NewMessage;
use App\Events\NewNotification;

class MessageController extends Controller
{
    // Get conversations list
    public function getConversations()
    {
        $userId = Auth::id();
        
        // Get unique users who have exchanged messages with the current user
        $conversations = Message::where('from_user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->get()
            ->map(function ($message) use ($userId) {
                // Get the other user's ID (not the current user)
                $otherUserId = $message->from_user_id == $userId 
                    ? $message->to_user_id 
                    : $message->from_user_id;
                
                return $otherUserId;
            })
            ->unique()
            ->values();
        
        // Get user details for each conversation
        $users = User::whereIn('id', $conversations)->get();
        
        return response()->json(['conversations' => $users]);
    }

    // Get user's conversations
    public function getConversationsList()
    {
        $userId = auth()->id();

        $conversations = Conversation::where('user_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->with(['user:id,name,email,profile_image', 'recipient:id,name,email,profile_image'])
            ->orderBy('last_message_time', 'desc')
            ->get()
            ->map(function ($conversation) use ($userId) {
                // Get the other user in the conversation
                $otherUser = $conversation->user_id === $userId 
                    ? $conversation->recipient 
                    : $conversation->user;

                return [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'profile_image' => $otherUser->profile_image,
                    'conversation_id' => $conversation->id,
                    'last_message' => $conversation->last_message,
                    'last_message_time' => $conversation->last_message_time,
                ];
            });

        return response()->json($conversations);
    }

    // Get messages between two users
    public function getMessages($otherUserId)
    {
        $userId = Auth::id();
        
        // Get or create conversation
        $conversation = Conversation::where(function($query) use ($userId, $otherUserId) {
            $query->where('user_id', $userId)
                  ->where('recipient_id', $otherUserId);
        })->orWhere(function($query) use ($userId, $otherUserId) {
            $query->where('user_id', $otherUserId)
                  ->where('recipient_id', $userId);
        })->first();
        
        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }
        
        // Get messages for the conversation
        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['sender:id,name,profile_image'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['messages' => $messages]);
    }

    // Send a new message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $userId = auth()->id();
        
        // Get or create conversation
        $conversation = Conversation::where(function($query) use ($userId, $request) {
            $query->where('user_id', $userId)
                  ->where('recipient_id', $request->recipient_id);
        })->orWhere(function($query) use ($userId, $request) {
            $query->where('user_id', $request->recipient_id)
                  ->where('recipient_id', $userId);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $userId,
                'recipient_id' => $request->recipient_id
            ]);
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'content' => $request->message,
        ]);

        // Update conversation last message
        $conversation->update([
            'last_message' => $request->message,
            'last_message_time' => now()
        ]);
        
        $message->load('sender');

        // Broadcast new message event
        broadcast(new NewMessage($message))->toOthers();

        // Broadcast notification to recipient
        $notification = [
            'id' => $message->id,
            'recipient_id' => $request->recipient_id,
            'sender' => [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'profile_image' => auth()->user()->profile_image
            ],
            'message' => $message->content,
            'created_at' => $message->created_at
        ];
        
        broadcast(new NewNotification($notification))->toOthers();

        return response()->json($message);
    }

    // Get unread messages count
    public function getUnreadCount()
    {
        $userId = auth()->id();
        
        // Get total unread count
        $totalUnreadCount = Message::whereHas('conversation', function($query) use ($userId) {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('recipient_id', $userId);
            });
        })
        ->where('sender_id', '!=', $userId)
        ->where('is_read', false)
        ->count();

        // Get per-conversation unread counts
        $conversationUnreadCounts = Message::whereHas('conversation', function($query) use ($userId) {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('recipient_id', $userId);
            });
        })
        ->where('sender_id', '!=', $userId)
        ->where('is_read', false)
        ->select('conversation_id', \DB::raw('COUNT(*) as unread_count'))
        ->groupBy('conversation_id')
        ->get()
        ->pluck('unread_count', 'conversation_id')
        ->toArray();
        
        return response()->json([
            'total_count' => $totalUnreadCount,
            'conversation_counts' => $conversationUnreadCounts
        ]);
    }

    /**
     * Create or get existing conversation with a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id'
        ]);

        $userId = auth()->id();
        $recipientId = $request->recipient_id;

        // Check if conversation already exists
        $conversation = Conversation::where(function($query) use ($userId, $recipientId) {
            $query->where('user_id', $userId)
                  ->where('recipient_id', $recipientId);
        })->orWhere(function($query) use ($userId, $recipientId) {
            $query->where('user_id', $recipientId)
                  ->where('recipient_id', $userId);
        })->first();

        if (!$conversation) {
            // Create new conversation
            $conversation = Conversation::create([
                'user_id' => $userId,
                'recipient_id' => $recipientId
            ]);
        }

        return response()->json([
            'conversation_id' => $conversation->id
        ]);
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function markRead($conversationId)
    {
        $userId = auth()->id();
        
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['success' => true]);
    }
}
