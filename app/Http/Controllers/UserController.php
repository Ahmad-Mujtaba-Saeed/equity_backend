<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $userData = $user->toArray();
        $userData['has_password'] = !empty($user->password);
        
        return response()->json($userData);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,'.$user->id,
            'city' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'website_url' => 'nullable|max:255',
            'email_notification' => 'nullable|boolean',
            'sms_notification' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $uploadPath = public_path('images/profiles');
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $image = $request->file('profile_image');
            $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Delete old image if exists
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                unlink(public_path($user->profile_image));
            }

            // Move the file to public directory
            $image->move($uploadPath, $fileName);
            $user->profile_image = 'profiles/' . $fileName;
        }

        // Update user fields
        $user->fill($request->except('profile_image'));
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Check if user has a password set
        if ($user->password) {
            if (!$request->current_password) {
                return response()->json(['error' => 'Current password is required'], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function updateNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notification' => 'required|boolean',
            'sms_notification' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $user->update($request->only(['email_notification', 'sms_notification']));

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'user' => $user
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'about' => [
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'marital_status' => $user->marital_status,
                'website_url' => $user->website_url,
            ]
        ]);
    }

    public function getUserPosts(Request $request)
    {
        $user = $request->user();
        $posts = $user->posts()->with(['user', 'comments.user', 'likes'])->latest()->get();
        return response()->json(['posts' => $posts]);
    }

    public function getUserComments(Request $request)
    {
        $user = $request->user();
        $comments = $user->comments()->with(['post', 'user'])->latest()->get();
        return response()->json(['comments' => $comments]);
    }

    public function getUserStats(Request $request)
    {
        $user = $request->user();
        $stats = [
            'posts_count' => $user->posts()->count(),
            'comments_count' => $user->comments()->count(),
            'likes_count' => $user->likes()->count(),
        ];
        return response()->json($stats);
    }
}
