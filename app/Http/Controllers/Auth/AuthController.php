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

    protected $firebaseAuth;

    public function __construct(AppAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }
    public function login(Request $request)
    {
        // Validate the incoming request for email and password
        $credentials = $request->validate([
            'firebase_uid' => 'nullable',
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
                // 'firebase_uid' => 'nullable',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        try {
            // $firebaseuser = $this->firebaseAuth->createUser([
            //     'email' => $request->email,
            //     'password' => $request->password,
            // ]);

            // return response()->json([
            //     'message' => 'User created successfully',
            //     'firebase_uid' => $user->uid,
            // ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            // 'firebase_uid' => $firebaseuser->uid ?? null,
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
    public function saveToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string|max:255',
        ]);
    
        try {
            // Get authenticated user
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
    
            // Store FCM token
            $user->fcm_token = $request->fcm_token;
            $user->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Token saved successfully'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
        try {
            $firebaseUser = $this->firebaseAuth->getUserByEmail($payload['email']);
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            // User does not exist in Firebase
            $firebaseuser = $this->firebaseAuth->createUser([
                'email' => $payload['email'],
                'emailVerified' => true,
            ]);
            // $user = User::updateOrCreate(
            //     ['email' => $payload['email']],
            //     [
            //         'name' => $payload['name'],
            //         'email' => $payload['email'],
            //         'google_id' => $payload['sub'], // Google Unique ID
            //         'profile_image' => $payload['picture'],
            //     ]
            // );
            if($firebaseuser->uid){
                $user->firebase_uid = $firebaseuser->uid;
                $user->save();
            }
        }


            // return response()->json([
            //     'message' => 'User created successfully',
            //     'firebase_uid' => $user->uid,
            // ], 201);
        // } catch (\Exception $e) {
        //     // return response()->json(['error' => $e->getMessage()], 500);
        // }

    
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
            // try {

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
            try {
                $firebaseUser = $this->firebaseAuth->getUserByEmail($payload['email']);
                // if($firebaseuser->uid){
                // $user->firebase_uid = $firebaseuser->uid;
                // }
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                // User does not exist, create them in Firebase
                $firebaseUser = $this->firebaseAuth->createUser([
                    'email' => $payload['email'],
                    'emailVerified' => true,
                ]);
                // $user = User::updateOrCreate(
                //     ['email' => $payload['email']],
                //     [
                //         'name' => $payload['name'],
                //         'google_id' => $payload['sub'],
    
                //         'email_verified_at' => now(),
                //     ]
                // );
                // if($firebaseuser->uid){
                $user->firebase_uid = $firebaseuser->uid;
                // }
                // $user_permission = UserPermission::updateOrCreate([
                //     'user_id' => $user->id,
                // ]);
                
                // $user->permission_id = $user_permission->id;
                // $user->save();
    
                // if (!$user->profile_image) {
                //     $user->profile_image = $payload['picture'];
                //     $user->save();
                // }
            }

            // if ($firebaseUser->uid) {
                // // User exists, sign them in
                                // Find or create user
        
        // }


                    // $firebaseuser = $this->firebaseAuth->signInWithEmail($payload['email']);
            //     } else {
            //         // firea$firebaseuser does not exist, create a new firea$firebaseuser
            //         $firebaseuser = $this->firebaseAuth->createUser([
            //             'email' => $payload['email'],
            //             'emailVerified' => true,  // User can't log in until email is verified
            //         ]);
            //                     // Find or create user
            // $user = User::updateOrCreate(
            //     ['email' => $payload['email']],
            //     [
            //         'name' => $payload['name'],
            //         'google_id' => $payload['sub'],

            //         'email_verified_at' => now(),
            //     ]
            // );
            // if($firebaseuser->uid){
            // $user->firebase_uid = $firebaseuser->uid;
            // }
            // $user_permission = UserPermission::updateOrCreate([
            //     'user_id' => $user->id,
            // ]);
            
            // $user->permission_id = $user_permission->id;
            // $user->save();

            // if (!$user->profile_image) {
            //     $user->profile_image = $payload['picture'];
            //     $user->save();
            // }


            //     }
            // } catch (\Exception $e) {
            //     // return response()->json(['error' => $e->getMessage()], 500);
            // }



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
