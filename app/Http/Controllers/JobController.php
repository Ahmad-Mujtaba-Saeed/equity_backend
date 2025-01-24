<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        $jobs = Job::where('is_active', true)
                   ->latest()
                   ->paginate(9);

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

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('main_image')) {
                $imagePath = $request->file('main_image')->store('images/jobs', 'public');
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
            if ($imagePath && Storage::disk('public')->exists("images/jobs/$imagePath")) {
                Storage::disk('public')->delete("images/jobs/$imagePath");
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

        if (Auth::id() !== $job->user_id) {
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
                if ($job->main_image && Storage::disk('public')->exists('images/jobs/' . $job->main_image)) {
                    Storage::disk('public')->delete('images/jobs/' . $job->main_image);
                }
                
                $data['main_image'] = $request->file('main_image')->store('images/jobs', 'public');
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

        if (Auth::id() !== $job->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Delete job image
            if ($job->main_image && Storage::disk('public')->exists('images/jobs/' . $job->main_image)) {
                Storage::disk('public')->delete('images/jobs/' . $job->main_image);
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
