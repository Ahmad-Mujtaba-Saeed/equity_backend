<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FollowsHandler;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use App\Models\EqNotification;
use App\Mail\PostNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function UserPosts()
    {

            $posts = Post::with(['user', 'likes', 'comments.user'])
                ->withCount(['likes', 'comments'])
                ->where('user_id', Auth::id())
                ->latest()
                ->paginate(10);


        // Log the raw posts data
        \Log::info('Raw posts data:', $posts->toArray());

        $posts->getCollection()->transform(function ($post) {
            $mediaArray = array_merge(
                json_decode($post->images, true) ?? [],
                json_decode($post->videos, true) ?? [],
                json_decode($post->documents, true) ?? []
            );
            
            $formattedMedia = [];
            
            foreach ($mediaArray as $mediaItem) {
                $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
                
                $formattedMedia[] = [
                    'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                    'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                ];
            }
            
            $post->media = $formattedMedia;


            return $post;
        });

        return response()->json($posts);
    }
    public function UserPostsforotherusers($id)
    {
        if($id){
            $posts = Post::with(['user', 'likes', 'comments.user'])
            ->withCount(['likes', 'comments'])
            ->where('user_id', $id)
            ->latest()
            ->paginate(10);
        }
        // Log the raw posts data
        \Log::info('Raw posts data:', $posts->toArray());

        $posts->getCollection()->transform(function ($post) {
            $mediaArray = array_merge(
                json_decode($post->images, true) ?? [],
                json_decode($post->videos, true) ?? [],
                json_decode($post->documents, true) ?? []
            );
            
            $formattedMedia = [];
            
            foreach ($mediaArray as $mediaItem) {
                $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
                
                $formattedMedia[] = [
                    'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                    'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                ];
            }
            
            $post->media = $formattedMedia;


            return $post;
        });

        return response()->json($posts);
    }
    private function applyBlurEffect($media) {
        // Decode JSON string to array if needed
        if (is_string($media)) {
            $media = json_decode($media, true); // Decode to associative array
        }
    
        // Assuming $media is now an array of image/video URLs or a single URL
        if (is_array($media)) {
            return array_map(function($item) {
                return $this->blurMedia($item);
            }, $media);
        }
        return $this->blurMedia($media);
    }
    
    private function blurMedia($url) {
        $path = public_path('data/images/' . basename($url));
        
        // Detect MIME type
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return $url; // Not a valid image
        }
        
        $mimeType = $imageInfo['mime'];
        $randomName = 'blurred_' . Str::random(20) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        $blurredPath = public_path('data/images/' . $randomName);
    
        // Create image resource based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($path);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($path);
                break;
            default:
                return $url; // Unsupported format
        }
    
        if ($image) {
            // Apply multiple blur effects
            for ($i = 0; $i < 95; $i++) {
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                imagefilter($image, IMG_FILTER_SELECTIVE_BLUR);
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            }
    
            // Save blurred image based on format
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($image, $blurredPath);
                    break;
                case 'image/png':
                    imagepng($image, $blurredPath);
                    break;
                case 'image/gif':
                    imagegif($image, $blurredPath);
                    break;
                case 'image/webp':
                    imagewebp($image, $blurredPath);
                    break;
            }
    
            imagedestroy($image); // Free memory
    
            return $randomName; // Return the random filename
        }
    
        return $url; // If image processing failed
    }
    
    
    
    public function index(Request $request)
    {
        $perPage = 6; // Set posts per page to 6
    
        // Get bearer token from request header
        $token = $request->bearerToken();
    
        if (!$token) {
            return response()->json([
                'data' => [],
                'current_page' => 0,
                'last_page' => 0,
                'has_more' => false
            ]);
        }
    
        try {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$user || !$user->tokenable) {
                return response()->json([
                    'data' => [],
                    'current_page' => 0,
                    'last_page' => 0,
                    'has_more' => false
                ]);
            }
    
            $userId = $user->tokenable->id;
    
            // Get list of followed users
            $followedUsers = FollowsHandler::where('follower_id', $userId)
                ->pluck('following_id')
                ->toArray();
    
            if (empty($followedUsers)) {
                return response()->json([
                    'data' => [],
                    'current_page' => 0,
                    'last_page' => 0,
                    'has_more' => false
                ]);
            }
    
            // Fetch posts only from followed users
            $query = Post::with([
                'user', 
                'likes', 
                'comments' => function($query) {
                    $query->with(['user', 'likes', 'replies', 'replies.likes','replies.user'])
                        ->withCount(['likes', 'replies']);
                }
            ])
                ->withCount(['likes', 'comments'])
                ->where(function ($q) use ($userId, $followedUsers) {
                    $q->whereIn('user_id', $followedUsers)
                      ->orWhere('user_id', $userId);
                })
                ->where(function ($q) use ($userId) {
                    $q->where('visibility', 'public')
                      ->orWhere('visibility', 'password_protected')
                      ->orWhere(function ($q2) use ($userId) {
                          // Allow private posts only if the user is the owner
                          $q2->where('visibility', 'private')->where('user_id', $userId);
                      });
                })
                ->latest();
    
            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category_id', $request->category);
            }
    
            // Paginate results
            $posts = $query->paginate($perPage);
    
            // Process each post
            $posts->getCollection()->transform(function ($post) use ($followedUsers, $userId) {
                // Set following status
                $post->is_following = in_array($post->user_id, $followedUsers);
    
                // Apply blur to password-protected posts if the user is not the owner
                if ($post->visibility == 'password_protected' && $post->user_id != $userId) {
                    $post->images = json_encode($this->applyBlurEffect($post->images));
    
                    $videos = [];
                    foreach (json_decode($post->videos, true) ?? [] as $video) {
                        $videos[] = "[Locked Video].mp4";
                    }
                    $post->videos = json_encode($videos);
                    $post->documents = json_encode([]);
                    $post->description = '<p style="filter: blur(5px);">
                                            This content is password protected. 
                                            You need a password to view this post.
                                          </p>';
                    $post->makeHidden(['documents', 'password']);
                }
    
                // Process media URLs
                $mediaArray = array_merge(
                    json_decode($post->images, true) ?? [],
                    json_decode($post->videos, true) ?? [],
                    json_decode($post->documents, true) ?? []
                );
    
                $formattedMedia = [];
                foreach ($mediaArray as $mediaItem) {
                    $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                    $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                    $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    
                    $formattedMedia[] = [
                        'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                        'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                    ];
                }
    
                $post->media = $formattedMedia;

                // foreach($post->comments as $comment) {
                //     $comment->is_liked = $comment->isLikedByCurrentUser();
                // }
    
                return $post;
            });
    
            // Return response
            return response()->json([
                'data' => $posts->items(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages()
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error fetching posts: ' . $e->getMessage());
    
            return response()->json([
                'data' => [],
                'current_page' => 0,
                'last_page' => 0,
                'has_more' => false
            ], 500);
        }
    }
    
    
    public function like_comment(Request $request)
    {
        $request->validate([
            'comment_id' => 'nullable|exists:comments,id',
        ]);

        try {
            $existingLike = Like::where('user_id', Auth::id())
                ->where('post_id', $request->comment_id)
                ->where('type', "comment")
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                return response()->json([
                    'success' => true,
                    'like_id' => $existingLike->id,
                    'message' => 'Comment unliked successfully'
                ], 200);
            } else {
                $like = Like::create([
                    'user_id' => Auth::id(),
                    'post_id' => $request->comment_id,
                    'type' => "comment"
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to like comment',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'like' => $like,
            'message' => 'Comment liked successfully'
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string',
            'description' => 'required|string',
            'visibility' => 'required|string|in:public,private,password_protected',
            'category_id' => 'required|exists:categories,id',
            'images' => 'array|nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:4086',
            'videos' => 'array|nullable',
            'videos.*' => 'mimes:mp4,mov,ogg|max:10240',
            'documents' => 'array|nullable',
            'documents.*' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:5120',
            
        ]);

        $permissions = json_decode(Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_post_category'), true);
        if (!in_array($request->category_id, $permissions)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        try {
            \DB::beginTransaction();
            
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->move(public_path('data/images'), $imageName);
                    $images[] = $imageName;
                }
            }

            $videos = [];
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $videoName = time() . '_' . $video->getClientOriginalName();
                    $video->move(public_path('data/videos'), $videoName);
                    $videos[] = $videoName;
                }
            }

            $documents = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $documentName = time() . '_' . $document->getClientOriginalName();
                    $document->move(public_path('data/documents'), $documentName);
                    $documents[] = $documentName;
                }
            }

            $post = Post::create([
                'visibility' => $request->visibility,
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'images' => json_encode($images),
                'videos' => json_encode($videos),
                'documents' => json_encode($documents),
            ]);
            if($request->visibility == 'password_protected'){
                $post->password = Hash::make($request->password);
                $post->save();
            }

            // If we get here, the post was created successfully
            \DB::commit();

            // Load relationships and counts like in show method
            $post->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
            $post->loadCount(['likes', 'comments']);

            // Check if user has liked the post
            $token = $request->bearerToken();
            if ($token) {
                try {
                    $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                    if ($user && $user->tokenable) {
                        $userId = $user->tokenable->id;
                        $post->liked = $post->likes->contains('user_id', $userId);
                        
                        // Check if user is following the post creator
                        $follow = FollowsHandler::where('follower_id',$userId)->where('following_id',$post->user->id)->first();
                        $post->is_following = $follow ? true : false;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error checking auth token: ' . $e->getMessage());
                }
            }

            // Format media files
            $mediaArray = array_merge(
                json_decode($post->images, true) ?? [],
                json_decode($post->videos, true) ?? [],
                json_decode($post->documents, true) ?? []
            );
            
            $formattedMedia = [];
            foreach ($mediaArray as $mediaItem) {
                $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
                
                $formattedMedia[] = [
                    'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                    'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                ];
            }
            
            $post->media = $formattedMedia;

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            
            // Log the error for debugging
            \Log::error('Post creation error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            // Clean up any uploaded files if there was an error
            $this->cleanupFiles($images, 'images');
            $this->cleanupFiles($videos, 'videos');
            $this->cleanupFiles($documents, 'documents');

            return response()->json([
                'message' => 'An error occurred while creating the post.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function cleanupFiles($files, $type)
    {
        if (empty($files)) return;

        foreach ($files as $file) {
            $path = public_path("data/$type/" . $file);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function show(Post $post, Request $request)
    {
        $post->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
        $post->increment('views_count');
        
        // Add likes_count and comments_count
        $post->loadCount(['likes', 'comments']);

        // Check if user has liked the post
        $token = $request->bearerToken();
        if ($token) {
            try {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($user && $user->tokenable) {
                    $userId = $user->tokenable->id;
                    if($post->visibility == 'private' && $post->user->id != $userId){
                        return Response::json([
                            'message' => 'You cannot view this post',
                        ], 403);
                    }
                    $post->liked = $post->likes->contains('user_id', $userId);
                    
                    // Check if user is following the post creator
                    $follow = FollowsHandler::where('follower_id', $userId)
                        ->where('following_id', $post->user->id)
                        ->first();
                    $post->is_following = $follow ? true : false;
                }
            } catch (\Exception $e) {
                \Log::error('Error checking auth token: ' . $e->getMessage());
            }
        }

        $token = $request->bearerToken();
        
        if ($token) {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($user && $user->tokenable) {
                    $userId = $user->tokenable->id;
                    if($post->user_id != $userId){

                    if($post->visibility == 'password_protected'){
                        // Apply a blur effect to images and videos instead of removing them
                        $post->images = json_encode($this->applyBlurEffect($post->images));
                        $videos = [];
                        foreach(json_decode($post->videos, true) as $video){
                            $videos[] = "[Locked Video].mp4";
                        }
                        $post->videos = json_encode($videos);
                        $post->documents = json_encode([]);
                        $post->description = '<p style="filter: blur(5px);">
                                                    This is a hardcore description that is intentionally blurred and not easily readable. 
                                                    It contains sensitive information that should not be disclosed to unauthorized users. 
                                                    The content is meant to convey a sense of mystery and intrigue, making it difficult to decipher.
                                            </p>';
                        $post->makeHidden(['documents','password']);
                    }
                    
                }
                }
                
                }else{
                    if($post->visibility == 'password_protected'){
                        // Apply a blur effect to images and videos instead of removing them
                        $post->images = json_encode($this->applyBlurEffect($post->images));
                        $videos = [];
                        foreach(json_decode($post->videos, true) as $video){
                            $videos[] = "[Locked Video].mp4";
                        }
                        $post->videos = json_encode($videos);
                        $post->documents = json_encode([]);
                        $post->description = '<p style="filter: blur(5px);">
                                                    This is a hardcore description that is intentionally blurred and not easily readable. 
                                                    It contains sensitive information that should not be disclosed to unauthorized users. 
                                                    The content is meant to convey a sense of mystery and intrigue, making it difficult to decipher.
                                            </p>';
                        $post->makeHidden(['documents','password']);
                    }
                }

        // Format media files
        $mediaArray = array_merge(
            json_decode($post->images, true) ?? [],
            json_decode($post->videos, true) ?? [],
            json_decode($post->documents, true) ?? []
        );
        
        $formattedMedia = [];
        foreach ($mediaArray as $mediaItem) {
            $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
            $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
            $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
            
            $formattedMedia[] = [
                'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
            ];
        }
        
        $post->media = $formattedMedia;
        
        return response()->json($post);
    }


    public function unlockPost(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'id' => 'required|integer|exists:posts,id',
            'password' => 'required|string',
        ]);
    
        // Find the post by ID
        $post = Post::find($request->id);
    
        // Check if the password matches
        if (Hash::check($request->password, $post->password)) {
                        // Load relationships and counts like in show method
                        $post->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
                        $post->loadCount(['likes', 'comments']);
            
                        // Check if user has liked the post
                        $token = $request->bearerToken();
                        if ($token) {
                            try {
                                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                                if ($user && $user->tokenable) {
                                    $userId = $user->tokenable->id;
                                    $post->liked = $post->likes->contains('user_id', $userId);
                                    
                                    // Check if user is following the post creator
                                    $follow = FollowsHandler::where('follower_id', $userId)
                                        ->where('following_id', $post->user->id)
                                        ->first();
                                    $post->is_following = $follow ? true : false;
                                }
                            } catch (\Exception $e) {
                                \Log::error('Error checking auth token: ' . $e->getMessage());
                            }
                        }
            
                        // Format media files
                        $mediaArray = array_merge(
                            json_decode($post->images, true) ?? [],
                            json_decode($post->videos, true) ?? [],
                            json_decode($post->documents, true) ?? []
                        );
                        
                        $formattedMedia = [];
                        foreach ($mediaArray as $mediaItem) {
                            $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                            $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                            $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
                            
                            $formattedMedia[] = [
                                'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                                'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                            ];
                        }
                        
                        $post->media = $formattedMedia;
            
                        return response()->json([
                            'message' => 'Post unlock successfully',
                            'post' => $post
                        ]);
        } else {
            // If the password is incorrect, return an error response
            return response()->json(['error' => 'Invalid password'], 403);
        }
    }


    public function update(Request $request, Post $post)
    {
        $request->validate([
            'visibility' => 'required|string|in:public,private,password_protected',
            'title' => 'required|string',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4086',
            'videos.*' => 'nullable|mimes:mp4,mov,ogg|max:10240',
            'documents.*' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:5120',
            'remove_media' => 'nullable|array',
            'remove_media.*' => 'string',
            'kept_images' => 'nullable|string',
            'kept_videos' => 'nullable|string',
            'kept_documents' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        // if ($request->errors()->count() > 0) {
        //     return response()->json(['error' => $request->errors()->first()], 422);
        // }


        try {
            \DB::beginTransaction();

            // Initialize arrays with kept media
            $images = json_decode($request->kept_images ?? '[]');
            $videos = json_decode($request->kept_videos ?? '[]');
            $documents = json_decode($request->kept_documents ?? '[]');

            // Handle media removal if specified
            if ($request->has('remove_media')) {
                foreach ($request->remove_media as $mediaPath) {
                    $fullPath = public_path('data/images/' . $mediaPath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                    $fullPath = public_path('data/videos/' . $mediaPath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                    $fullPath = public_path('data/documents/' . $mediaPath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            // Handle new media uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->move(public_path('data/images'), $imageName);
                    $images[] = $imageName;
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $videoName = time() . '_' . $video->getClientOriginalName();
                    $video->move(public_path('data/videos'), $videoName);
                    $videos[] = $videoName;
                }
            }

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $documentName = time() . '_' . $document->getClientOriginalName();
                    $document->move(public_path('data/documents'), $documentName);
                    $documents[] = $documentName;
                }
            }

            // Update post with new data
            $post->update([
                'visibility' => $request->visibility,
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'images' => json_encode($images),
                'videos' => json_encode($videos),
                'documents' => json_encode($documents),
            ]);

            if($request->visibility === "password_protected"){
                $post->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            \DB::commit();

            // Load relationships and counts like in show method
            $post->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
            $post->loadCount(['likes', 'comments']);

            // Check if user has liked the post
            $token = $request->bearerToken();
            if ($token) {
                try {
                    $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                    if ($user && $user->tokenable) {
                        $userId = $user->tokenable->id;
                        $post->liked = $post->likes->contains('user_id', $userId);
                        
                        // Check if user is following the post creator
                        $follow = FollowsHandler::where('follower_id', $userId)
                            ->where('following_id', $post->user->id)
                            ->first();
                        $post->is_following = $follow ? true : false;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error checking auth token: ' . $e->getMessage());
                }
            }

            // Format media files
            $mediaArray = array_merge(
                json_decode($post->images, true) ?? [],
                json_decode($post->videos, true) ?? [],
                json_decode($post->documents, true) ?? []
            );
            
            $formattedMedia = [];
            foreach ($mediaArray as $mediaItem) {
                $extension = pathinfo($mediaItem, PATHINFO_EXTENSION);
                $isVideo = in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
                $isDocument = in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
                
                $formattedMedia[] = [
                    'type' => $isVideo ? 'video' : ($isDocument ? 'document' : 'image'),
                    'url' => url('data/' . ($isVideo ? 'videos' : ($isDocument ? 'documents' : 'images')) . '/' . $mediaItem)
                ];
            }
            
            $post->media = $formattedMedia;

            return response()->json([
                'message' => 'Post updated successfully',
                'post' => $post
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            
            // Log the error for debugging
            \Log::error('Post update error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'An error occurred while updating the post.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== Auth::id() && Auth::user()->roles !== "admin") {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete images
        if ($post->images) {
            foreach (json_decode($post->images, true) as $image) {
                $imagePath = public_path('data/images/' . $image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        // Delete videos
        if ($post->videos) {
            foreach (json_decode($post->videos, true) as $video) {
                $videoPath = public_path('data/videos/' . $video);
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            }
        }

        // Delete documents
        if ($post->documents) {
            foreach (json_decode($post->documents, true) as $document) {
                $documentPath = public_path('data/documents/' . $document);
                if (file_exists($documentPath)) {
                    unlink($documentPath);
                }
            }
        }

        $post->delete();

        return response()->json(['message' => 'Post and associated media deleted successfully']);
    }

    public function like(Post $post)
    {
        $like = $post->likes()->where('user_id', Auth::id())->first();
        $isLiked = false;

        if ($like) {
            // Delete the like notification
            EqNotification::where('foreign_id', $like->id)
                ->where('notif_type', 'like')
                ->delete();
                
            $like->delete();
            $message = 'Post unliked successfully';
            $isLiked = false;
        } else {
            $like = $post->likes()->create(['user_id' => Auth::id()]);
            $message = 'Post liked successfully';
            $isLiked = true;

            // Load the post content
            $post->load('user');

            // Create notification for post owner
            if ($post->user_id !== Auth::id()) {
                $user = User::find($post->user_id);
                if ($user && $user->fcm_token) {
                    $firebaseResponse = sendPushNotification($user, "Liked post",  Auth::user()->name . ' liked your post');
                    \Log::info('FCM Response: ', $firebaseResponse);
                }
                EqNotification::create([
                    'user_id' => $post->user_id,
                    'by_user' => Auth::id(),
                    'foreign_id' => $post->id,
                    'notif_type' => 'like',
                    'content' => Auth::user()->name . ' liked your post'
                ]);

                // Send email notification
                $emailData = [
                    'type' => 'like',
                    'recipient_name' => $post->user->name,
                    'post_title' => $post->title ? Str::limit($post->title, 100) : 'Post',
                    'post_id' => $post->id,
                    'actor_name' => Auth::user()->name
                ];

                Mail::to($post->user->email)->queue(new PostNotification($emailData));
            }
        }

        $likes = $post->likes()->with('user')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'liked' => $isLiked,
            'likes' => $likes,
            'likes_count' => $likes->count()
        ]);
    }

    public function comment(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
            'reply_to' => 'nullable|exists:users,id'
        ]);
        $mediaPaths = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = 'commentData/' . $file->getClientOriginalName();
                $mediaPaths[] = $path;
                $file->move(public_path('commentData'), $file->getClientOriginalName());
            }
        }

        $comment = $post->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'media' => json_encode($mediaPaths), // Store paths as JSON
            'parent_id' => $request->parent_id,
            'reply_to' => $request->reply_to
        ]);

        // Load the post content
        $post->load('user');

        // Create notification for post owner
        if ($post->user_id !== Auth::id()) {
            EqNotification::create([
                'user_id' => $post->user_id,
                'by_user' => Auth::id(),
                'foreign_id' => $post->id,
                'notif_type' => 'comment',
                'content' => Auth::user()->name . ' commented on your post'
            ]);

            // Send email notification
            $emailData = [
                'type' => 'comment',
                'recipient_name' => $post->user->name,
                'post_title' => $post->title ? Str::limit($post->title, 100) : 'Post',
                'post_id' => $post->id,
                'actor_name' => Auth::user()->name
            ];

            Mail::to($post->user->email)->queue(new PostNotification($emailData));
        }

        // If this is a reply, also notify the parent comment owner
        if ($request->parent_id) {
            $parentComment = $comment->parent;
            if ($parentComment && $parentComment->user_id !== Auth::id()) {
                EqNotification::create([
                    'user_id' => $parentComment->user_id,
                    'by_user' => Auth::id(),
                    'foreign_id' => $post->id,
                    'notif_type' => 'reply',
                    'content' => Auth::user()->name . ' replied to your comment'
                ]);

                // Send email notification for reply
                $emailData = [
                    'type' => 'reply',
                    'recipient_name' => $parentComment->user->name,
                    'post_title' => Str::limit($post->title, 100),
                    'post_id' => $post->id,
                    'actor_name' => Auth::user()->name
                ];

                Mail::to($parentComment->user->email)->queue(new PostNotification($emailData));
            }
        }
        
        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user')
        ], 201);
    }
}
