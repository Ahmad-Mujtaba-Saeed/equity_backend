<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\UserPermission;

use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as AppAuth;
use Google_Client;




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
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        $user_permission = UserPermission::updateOrCreate([
            'user_id' => $user->id,
        ]);

        $user->permission_id = $user_permission->id;
        $user->save();

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

    // public function handleGoogleCallbackForApp(Request $request)
    // {
    //     try {
    //         $idToken = $request->input('id_token');
    
    //         if (!$idToken) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'ID token is required'
    //             ], 400);
    //         }
    
    //         // ✅ Use Firebase Admin SDK to verify token
    //         $firebase = (new Factory)
    //             ->withServiceAccount(config('services.firebase.credentials')) // ✅ Ensure this path is correct
    //             ->createAuth();
    
    //         $verifiedIdToken = $firebase->verifyIdToken($idToken);
    
    //         if (!$verifiedIdToken) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Invalid ID token'
    //             ], 401);
    //         }
    
    //         // ✅ Extract user details
    //         $uid = $verifiedIdToken->claims()->get('sub'); // Google UID
    //         $email = $verifiedIdToken->claims()->get('email');
    //         $name = $verifiedIdToken->claims()->get('name');
    //         $avatar = $verifiedIdToken->claims()->get('picture');
    
    //         if (!$email) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Invalid token'
    //             ], 401);
    //         }
    
    //         // ✅ Find or create user in database
    //         $user = User::updateOrCreate(
    //             ['email' => $email],
    //             [
    //                 'name' => $name,
    //                 'google_id' => $uid,
    //                 'email_verified_at' => now(),
    //                 'profile_image' => $avatar,
    //             ]
    //         );
    
    //         // ✅ Generate access token
    //         $token = $user->createToken('google-token')->plainTextToken;
    
    //         return response()->json([
    //             'status' => true,
    //             'access_token' => $token,
    //             'token_type' => 'Bearer',
    //             'user' => $user
    //         ]);
    //     } catch (Exception $e) {
    //         \Log::error('Google authentication error: ' . $e->getMessage());
    
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to authenticate with Google',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function handleGoogleLoginRequestApp(Request $request) {
        $googleToken = $request->input('id_token');

        if (!$googleToken) {
            return response()->json(['error' => 'Missing Google token'], 400);
        }
    
        // Manually verify token with Google's API
        $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token=" . $googleToken);
    
        if ($response->failed()) {
            return response()->json(['error' => 'Invalid Google token'], 401);
        }
    
        $payload = $response->json();
        $user = User::updateOrCreate(
            ['email' => $payload['email']],
            [
                'name' => $payload['name'],
                'email' => $payload['email'],
                'google_id' => $payload['sub'], // Google Unique ID
                'profile_image' => $payload['picture'],
            ]
        );
    
        // Generate Laravel JWT token
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json(['user' => $user, 'access_token' => $token]);
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
                    'email_verified_at' => now(),
                ]
            );
            $user_permission = UserPermission::updateOrCreate([
                'user_id' => $user->id,
            ]);
            
            $user->permission_id = $user_permission->id;
            $user->save();

            if (!$user->profile_image) {
                $user->profile_image = $payload['picture'];
                $user->save();
            }



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



    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        Mail::send('emails.forgot-password', ['token' => $token], function($message) use($request){
            $message->to($request->email);
            $message->subject('Reset Password Notification');
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });

        return response()->json([
            'message' => 'Password reset link sent to your email.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $updatePassword = DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token,
            ])
            ->first();

        if(!$updatePassword) {
            return response()->json(['error' => 'Invalid token!'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where(['email'=> $request->email])->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
