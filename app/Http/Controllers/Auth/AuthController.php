<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;
use Google\Client as GoogleClient;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate the incoming request for email and password
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        // Attempt to log the user in with the provided credentials
        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        // Get the authenticated user
        $user = auth()->user();
    
        // Create a Sanctum token for the user
        $token = $user->createToken('auth_token')->plainTextToken;
    
        // Return the token in the response
        return response()->json(['access_token' => $token]);
    }

    public function register(Request $request){
        // Validate the incoming request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        // Generate token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return user & token response
        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $idToken = $request->input('id_token');

            if (!$idToken) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID token is required'
                ], 400);
            }

            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            // Find or create user
            $user = User::updateOrCreate(
                ['email' => $payload['email']],
                [
                    'name' => $payload['name'],
                    'google_id' => $payload['sub'],
                    'profile_image' => $payload['picture'] ?? null,
                    'email_verified_at' => now(),
                ]
            );

            // Create token for the user
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
