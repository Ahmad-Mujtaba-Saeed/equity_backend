<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication; // Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $jobs = Job::where('is_active', true)
                   ->latest()
                   ->paginate(9);
        // Get bearer token from request header
        $token = $request->bearerToken();
                   if ($token) {
                    try {
                        // Attempt to get user from token
                        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                        if ($user && $user->tokenable) {
                            $userId = $user->tokenable->id;
                            foreach($jobs as $job) {
                                // Fetch applications for the current job by user ID
                                $job->application = JobApplication::where('job_id', $job->id)
                                    ->where('user_id', $userId)
                                    ->first();
                            }
        
                            // // Sort posts to show is_following=true posts first
                            // $posts = $posts->sortByDesc(function($post) {
                            //     return [$post->is_following, $post->created_at];
                            // })->values();
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error checking auth token: ' . $e->getMessage());
                    }
                }

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_jobs') !== 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('main_image')) {
                $image = $request->file('main_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/jobs'), $imageName);
                $imagePath = 'jobs/' . $imageName;
            }

            $job = Job::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'main_image' => $imagePath,
            ]);

            return response()->json([
                'message' => 'Job created successfully',
                'job' => $job->load('user')
            ], 201);

        } catch (\Exception $e) {
            // Delete uploaded image if job creation fails
            if ($imagePath && file_exists(public_path($imagePath))) {
                unlink(public_path($imagePath));
            }

            return response()->json([
                'message' => 'Error creating job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $job = Job::findOrFail($id);
        
        if (!$job->is_active && (!Auth::check() || Auth::id() !== $job->user_id)) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json([
            'job' => $job
        ]);
    }

    public function update(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        if (Auth::id() !== $job->user_id && Auth::user()->roles != "admin") {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $data = $request->only(['title', 'short_description', 'description', 'is_active']);

            // Handle image upload
            if ($request->hasFile('main_image')) {
                // Delete old image
                if ($job->main_image && file_exists(public_path($job->main_image))) {
                    unlink(public_path($job->main_image));
                }
                
                $image = $request->file('main_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/jobs'), $imageName);
                $data['main_image'] = 'images/jobs/' . $imageName;
            }

            $job->update($data);

            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job->fresh('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $job = Job::findOrFail($id);

        if (Auth::id() !== $job->user_id && Auth::user()->roles != "admin") {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Delete job image
            if ($job->main_image && file_exists(public_path($job->main_image))) {
                unlink(public_path($job->main_image));
            }

            $job->delete();

            return response()->json([
                'message' => 'Job deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting job',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
