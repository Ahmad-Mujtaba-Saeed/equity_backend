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
        return response()->json($request->user());
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
            'website_url' => 'nullable|url|max:255',
            'email_notification' => 'nullable|boolean',
            'sms_notification' => 'nullable|boolean',
            'profile_image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $user->profile_image = $path;
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
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
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
}
