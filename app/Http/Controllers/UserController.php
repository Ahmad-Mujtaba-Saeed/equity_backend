<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Auth;
use App\Models\FollowsHandler;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('permissions');
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
            'gender' => 'nullable',
            'date_of_birth' => 'nullable',
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

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $uploadPath = public_path('images/backgroundprofilepic');
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $image = $request->file('background_image');
            $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Delete old background image if exists and it's not the default
            if ($user->background_image && $user->background_image !== 'background-img.jpg' && file_exists(public_path('images/backgroundprofilepic/' . $user->background_image))) {
                unlink(public_path('images/backgroundprofilepic/' . $user->background_image));
            }

            // Move the file to public directory
            $image->move($uploadPath, $fileName);
            $user->background_image = $fileName;
        }

        // Update user fields
        $user->fill($request->except(['profile_image', 'background_image']));
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

    public function getProfileforotheruser($id,Request $request)
    {
        $user = User::find($id);
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
    public function getUserStatsforotheruser($id,Request $request)
    {
        $user = User::find($id);
        $stats = [
            'posts_count' => $user->posts()->count(),
            'comments_count' => $user->comments()->count(),
            'likes_count' => $user->likes()->count(),
        ];
        return response()->json($stats);
    }
    public function getUserPostsforotheruser($id,Request $request)
    {
        $user = User::find($id);
        $posts = $user->posts()->with(['user', 'comments.user', 'likes'])->latest()->get();
        return response()->json(['posts' => $posts]);
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

    // Get users list with basic info
    public function getUsers(Request $request)
    {
        $search = $request->query('search', '');
        $currentUserId = auth()->id();

        $query = User::select('id', 'name', 'email','username', 'profile_image')
            ->where('id', '!=', $currentUserId); // Exclude current user

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
                      ->limit(50)
                      ->get();

        return response()->json([
            'users' => $users
        ]);
    }

    public function getAdmins(Request $request)
    {
        
        $search = $request->query('search', '');
        $currentUserId = auth()->id();

        $query = User::select('id', 'name', 'email','roles','username', 'profile_image')
            ->where('id', '!=', $currentUserId)
            ->where(function($q) {
                $q->where('roles', 'creators')->orWhere('roles', 'admin');
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
                      ->limit(50)
                      ->get();

        foreach($users as $user){
        $follow = FollowsHandler::where('follower_id',$currentUserId)->where('following_id',$user->id)->first();
            if($follow){
                $user->is_following = true;
            }else{
                $user->is_following = false;
            }
        }
        return response()->json([
            'users' => $users
        ]);
    }

    public function GetUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->load('permissions');
        return response()->json($user);
    }

    public function UpdatePermissions(Request $request, $id)
    {
        // Validate the incoming request
        $request->validate([
            'make_admin' => 'required|boolean',
            // 'can_create_jobs' => 'required|boolean',
            // 'can_create_events' => 'required|boolean',
            // 'can_create_education' => 'required|boolean',
            // 'can_create_post_business' => 'required|boolean',
            // 'can_create_post_fitness' => 'required|boolean',
            // 'can_create_post_crypto' => 'required|boolean',
            // 'can_create_post_mindset' => 'required|boolean',
            // 'can_manage_users' => 'required|boolean',
        ]);
    

        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_manage_users') !== 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the user
        $user = User::findOrFail($id);

        if($request->make_admin){
            $can_create_post_category = json_encode([
                 1,
                2,
                 3,
                 4 ,
                 5 ,
           ]);
            $permissionsData = [
                'can_create_jobs' => true,
                'can_create_events' => true,
                'can_create_education' => true,
                'can_create_post_category' => $can_create_post_category,
                'can_manage_users' => false
            ];
            $user->roles = "creators";
            $user->save();
        }
        else{
            $can_create_post_category = json_encode([
                0,
               0,
                0,
                0 ,
                0 ,
          ]);
            $permissionsData = [
                'can_create_jobs' => false,
                'can_create_events' => false,
                'can_create_education' => false,
                'can_create_post_category' => $can_create_post_category,
                'can_manage_users' => false
            ];
            $user->roles = "user";
            $user->save();
        }
    
        // $request->can_create_post_category = json_encode([
        //      $request->can_create_post_business ? 1 :0,
        //      $request->can_create_post_fitness ? 2 :0,
        //      $request->can_create_post_crypto ? 3 :0,
        //      $request->can_create_post_mindset ? 4 :0,
        //      $request->can_create_post_mindset ? 5 :0,
        // ]);
    
        // // Update permissions
        // $permissionsData = [
        //     'can_create_jobs' => $request->can_create_jobs,
        //     'can_create_events' => $request->can_create_events,
        //     'can_create_education' => $request->can_create_education,
        //     'can_create_post_category' => $request->can_create_post_category,
        //     'can_manage_users' => $request->can_manage_users
        // ];
    
        // Use the user_id to update or create the permissions
        UserPermission::updateOrCreate(
            ['user_id' => $user->id], // Use user_id as the identifier
            $permissionsData
        );

        // if($request->can_create_post_business ||
        //     $request->can_create_post_fitness ||
        //     $request->can_create_post_crypto ||
        //     $request->can_create_post_mindset ||
        //     $request->can_create_post_technology){
            


        // }
    
        return response()->json(['message' => 'Permissions updated successfully.']);
    }
    /**
     * Search for users based on query
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // dd();
        $query = $request->get('query');
        
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email', 'profile_image')
            ->limit(5)
            ->get();

        return response()->json($users);
    }
}

