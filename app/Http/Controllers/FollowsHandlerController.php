<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowsHandler;
use Illuminate\Support\Facades\Auth;

class FollowsHandlerController extends Controller
{
    public function toggleFollow($following_id)
    {
        try {
            $follower_id = Auth::id();
            
            // Check if already following
            $existingFollow = FollowsHandler::where('follower_id', $follower_id)
                ->where('following_id', $following_id)
                ->first();

            if ($existingFollow) {
                // Unfollow
                $existingFollow->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Unfollowed successfully',
                    'action' => 'unfollow'
                ]);
            }

            // Create new follow
            FollowsHandler::create([
                'follower_id' => $follower_id,
                'following_id' => $following_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Followed successfully',
                'action' => 'follow'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred'
            ], 500);
        }
    }
}
