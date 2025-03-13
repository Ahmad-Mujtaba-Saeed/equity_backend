<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use App\Models\UserPermission;

class SocialAuthController extends Controller
{
    // Redirect to Facebook
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function facebookDataDeletion(Request $request){
        Log::info('Facebook Data Deletion Request:', $request->all());

        // Get the Facebook user ID from the request
        $facebookUserId = $request->input('facebook_user_id');
    
        if (!$facebookUserId) {
            return response()->json(['error' => 'Invalid request. Facebook User ID is required.'], 400);
        }
    
        // Find and delete the user
        $user = User::where('facebook_id', $facebookUserId)->first();
    
        if ($user) {
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User data deleted successfully.',
            ]);
        }
    
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 404);
    }

    // Handle Facebook Callback
    public function handleFacebookCallback(Request $request)
    {
        try {
            $accessToken = $request->input('access_token');
    
            if (!$accessToken) {
                return response()->json(['error' => 'Access token is required'], 400);
            }
    
            // Verify the access token with Facebook's API
            $fbResponse = Http::get("https://graph.facebook.com/me", [
                'fields' => 'id,name,email,picture',
                'access_token' => $accessToken
            ]);
    
            $facebookUser = $fbResponse->json();
    
            if (!isset($facebookUser['id'])) {
                return response()->json(['error' => 'Invalid Facebook token'], 401);
            }
    
            // Find or create the user
            $user = User::updateOrCreate(
                ['email' => $facebookUser['email'] ?? "fb_{$facebookUser['id']}@facebook.com"], // Some users may not have an email
                [
                    'name' => $facebookUser['name'],
                    'facebook_id' => $facebookUser['id'],
                    'profile_image' => $facebookUser['picture']['data']['url'] ?? null,
                ]
            );
    
            // Create user permission if not exists
            $user_permission = UserPermission::firstOrCreate(['user_id' => $user->id]);
    
            $user->permission_id = $user_permission->id;
            $user->save();
    
            Auth::login($user);
    
            return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed', 'message' => $e->getMessage()], 500);
        }
    }

    // Redirect to Instagram
    public function redirectToInstagram()
    {
        return Socialite::driver('instagram')->redirect();
    }

    // Handle Instagram Callback
    public function handleInstagramCallback()
    {
        try {
            $instagramUser = Socialite::driver('instagram')->user();

            $user = User::updateOrCreate(
                ['email' => $instagramUser->getEmail()],
                [
                    'name' => $instagramUser->getName(),
                    'instagram_id' => $instagramUser->getId(),
                    // 'profile_image' => $instagramUser->getAvatar(),
                    // 'password' => bcrypt(uniqid())
                ]
            );
            $user_permission = UserPermission::updateOrCreate([
                'user_id' => $user->id,
            ]);
            
            $user->permission_id = $user_permission->id;
            $user->save();

            if (!$user->profile_image) {
                $user->profile_image = $instagramUser->getAvatar();
                $user->save();
            }

            Auth::login($user);

            return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed'], 500);
        }
    }
}
