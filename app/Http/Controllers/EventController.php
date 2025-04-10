<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query()->with('creator','organizer');
        
        // Filter by month and year if provided
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('event_date', $request->month)
                  ->whereYear('event_date', $request->year);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $query->where('is_active', 1)->orWhere('created_by', Auth::id());

        $events = $query->orderBy('event_date', 'asc')->get();


        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'organizer_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'media.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,txt|max:2048', // Accepts multiple files
            'subtitle' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            // 'type' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_events') !== 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mediaFiles = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('data/images'), $fileName);
                $mediaFiles[] = [
                    'name' => $fileName,
                    'type' => $file->getClientOriginalExtension(),
                ];
            }
        }
        
        
        $eventData = $validator->validated();
        unset($eventData['media']); // Remove media from validated data since we'll handle it separately
        // Handle the main image upload
        if (isset($request->main_image)) {
            $imageData = $request->main_image;
            $imageName = time() . '_main_image.jpg'; // Create a unique name for the image
            $imagePath = public_path('data/images/' . $imageName);
    
            // Decode the base64 string
            $image = str_replace('data:image/jpeg;base64,', '', $imageData);
            $image = str_replace(' ', '+', $image);
            file_put_contents($imagePath, base64_decode($image)); // Save the image
    
            $eventData['main_image'] = 'data/images/' . $imageName; // Save the relative path in the event data
        }

        if (isset($request->banner_image)) {
            $imageData = $request->banner_image;
            $imageName = time() . '_banner_image.jpg'; // Create a unique name for the image
            $imagePath = public_path('data/images/' . $imageName);
    
            // Decode the base64 string
            $image = str_replace('data:image/jpeg;base64,', '', $imageData);
            $image = str_replace(' ', '+', $image);
            file_put_contents($imagePath, base64_decode($image)); // Save the image
    
            $eventData['banner_image'] = 'data/images/' . $imageName; // Save the relative path in the event data
        }



        $eventData['created_by'] = Auth::id();

        $event = Event::create($eventData);
        if (!empty($mediaFiles)) {
            $event->media = json_encode($mediaFiles);
            $event->save();
        }
        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Event::with('creator')->findOrFail($id);
        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
    
        $validator = Validator::make($request->all(), [
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subtitle' => 'nullable|string|max:255',
            'organizer_id' => 'nullable|integer',
            'event_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'type' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $eventData = $validator->validated();
    
        // Handle the main image upload
        if ($request->hasFile('main_image')) {
            // Move the uploaded image to public/data/images directory
            $image = $request->file('main_image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Create a unique name for the image
            $image->move(public_path('data/images'), $imageName); // Move the image to the specified path
            $eventData['main_image'] = 'data/images/' . $imageName; // Save the relative path in the event data
        }
    
        $event->update($eventData);
        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        
        $event->delete();
        return response()->json(null, 204);
    }
}
