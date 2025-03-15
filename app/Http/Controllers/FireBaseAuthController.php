<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\Models\User;
use Exception;

class FireBaseAuthController extends Controller
{
    public function handleGoogleCallbackForApp(Request $request)
    {
        try {
            $idToken = $request->input('id_token');

            if (!$idToken) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID token is required'
                ], 400);
            }

            // ✅ Initialize Firebase Admin SDK
            $firebase = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->createAuth();

            // ✅ Verify ID Token using Firebase
            $verifiedIdToken = $firebase->verifyIdToken($idToken);

            $uid = $verifiedIdToken->claims()->get('sub'); // Google UID
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            if (!$email) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            // ✅ Find or create user
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'google_id' => $uid,
                    'email_verified_at' => now(),
                    'profile_image' => $avatar,
                ]
            );

            // ✅ Create Laravel Sanctum Access Token
            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (Exception $e) {
            \Log::error('Google authentication error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to authenticate with Google',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
