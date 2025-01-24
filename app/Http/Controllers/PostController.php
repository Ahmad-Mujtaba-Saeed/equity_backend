<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 6; // Set posts per page to 6

        $posts = Post::with(['user', 'likes', 'comments.user'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($perPage);

        // Get bearer token from request header
        $token = $request->bearerToken();
        
        if ($token) {
            try {
                // Attempt to get user from token
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($user && $user->tokenable) {
                    $userId = $user->tokenable->id;
                    foreach($posts as $post) {
                        $post->liked = $post->likes->contains('user_id', $userId);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error checking auth token: ' . $e->getMessage());
            }
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

        return response()->json([
            'data' => $posts->items(),
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'has_more' => $posts->hasMorePages()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'images' => 'array|nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'array|nullable',
            'videos.*' => 'mimes:mp4,mov,ogg|max:10240',
            'documents' => 'array|nullable',
            'documents.*' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:5120',
        ]);

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

        try {
            $post = Post::create([
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'title' => $request->content,
                'description' => $request->content,
                'images' => json_encode($images),
                'videos' => json_encode($videos),
                'documents' => json_encode($documents),
            ]);

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post->load(['user', 'category'])
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Post creation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Post $post)
    {
        $post->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
        $post->increment('views_count');
        
        return response()->json($post);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $post->update($request->only(['title', 'description']));

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post
        ]);
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
            $like->delete();
            $message = 'Post unliked successfully';
            $isLiked = false;
        } else {
            $post->likes()->create(['user_id' => Auth::id()]);
            $message = 'Post liked successfully';
            $isLiked = true;
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
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        $comment = $post->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id
        ]);
        
        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user')
        ], 201);
    }
}
