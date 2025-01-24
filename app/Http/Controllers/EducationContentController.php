<?php

namespace App\Http\Controllers;

use App\Models\EducationContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'video_url' => 'nullable|url'
        ]);
        if(Auth::user()->roles !== "admin"){
            return response()->json(['error' => 'Only admin can upload educational content'], 400);
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

        // Check if the user is the owner of the content
        if ($educationContent->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
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
        
        // Check if the user is the owner of the content
        if ($educationContent->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete the image file
        if ($educationContent->image_path) {
            $imagePath = public_path('data/images/education/' . $educationContent->image_path);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $educationContent->delete();
        return response()->json(null, 204);
    }
}
