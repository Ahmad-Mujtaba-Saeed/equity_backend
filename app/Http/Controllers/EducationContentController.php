<?php

namespace App\Http\Controllers;

use App\Models\EducationContent;
use App\Models\VideoCompletion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EducationContentController extends Controller
{
    public function index()
    {
        return EducationContent::with('user:id,name')->latest()->get();
    }

    public function show($id)
    {
        return EducationContent::with('user:id,name')->findOrFail($id);
    }

    public function videoPoints(Request $request){
        $request->validate([
            'content_id' => 'required',
            'user_id' => 'required',
        ]);

        $content = EducationContent::findOrFail($request->content_id);
        
        // Check if video has already been completed by this user
        $existing = VideoCompletion::where('user_id', $request->user_id)
            ->where('content_id', $request->content_id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Video already completed'], 200);
        }

        try {
            DB::beginTransaction();

            // Create video completion record
            VideoCompletion::create([
                'user_id' => $request->user_id,
                'content_id' => $request->content_id
            ]);

            // Update user's award points
            $user = User::findOrFail($request->user_id);
            $user->increment('award_points');

            DB::commit();

            return response()->json([
                'message' => 'Points awarded successfully',
                'award_points' => $user->award_points
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to award points'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'video_url' => 'required|url'
        ]);
        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_education') !== 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            
            // Move the file to the public/data/images/education directory
            $image->move(public_path('data/images/education'), $imageName);

            $educationContent = EducationContent::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'image_path' => $imageName,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'video_url' => $request->video_url
            ]);

            return response()->json($educationContent, 201);
        }

        return response()->json(['error' => 'Image upload failed'], 400);
    }

    public function update(Request $request, $id)
    {
        $educationContent = EducationContent::findOrFail($id);


        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_education') !== 1){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        


        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'video_url' => 'nullable|url'
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image
            if ($educationContent->image_path) {
                $oldImagePath = public_path('data/images/education/' . $educationContent->image_path);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Upload new image
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('data/images/education'), $imageName);
            $data['image_path'] = $imageName;
        }

        $educationContent->update($data);
        return response()->json($educationContent);
    }

    public function destroy($id)
    {
        $educationContent = EducationContent::findOrFail($id);
        
        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_education') !== 1){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete the image file
        if ($educationContent->image_path) {
            $imagePath = public_path('data/images/education/' . $educationContent->image_path);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $educationContent->delete();
        return response()->json(['message' => 'Content deleted successfully'], 204);
    }
}
