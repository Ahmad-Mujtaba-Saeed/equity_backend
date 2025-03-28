<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:jobs,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country' => 'required|string|max:2',
            'company' => 'required|string|max:255',
            'cv' => 'required',
            'job_title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user has already applied for this job
        $existingApplication = JobApplication::where('user_id', Auth::id())
            ->where('job_id', $request->job_id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job'
            ], 422);
        }

        try {
            $user = Auth::user();
            // Create job application with user_id
            $cvFilePath = null;
        if ($request->hasFile('cv')) {
            $cvFile = $request->file('cv');
            $cvFileName = time() . '_' . $user->id . '_' . $cvFile->getClientOriginalName();
            
            // Move file to public/data/documents/job_applications
            $cvFile->move(public_path('data/documents/job_applications'), $cvFileName);
            
            $cvFilePath = 'data/documents/job_applications/' . $cvFileName;
        }
            $application = JobApplication::create(array_merge($request->all(), [
                'user_id' => Auth::id(),
                'status' => 'pending',
                'cv_file_path' => $cvFilePath
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Job application submitted successfully',
                'data' => $application
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation
            if ($e->getCode() === '23505') { // PostgreSQL unique violation code
                return response()->json([
                    'success' => false,
                    'message' => 'You have already applied for this job'
                ], 422);
            }
            throw $e;
        }
    }

    public function index()
    {
        $applications = JobApplication::with(['job', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'applications' => $applications
        ]);
    }

    public function update(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);

        if (Auth::user()->permissions()->where('user_id', Auth::id())->value('can_create_jobs') !== 1){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,accepted,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $application->update($request->only('status'));

        return response()->json([
            'message' => 'Application status updated successfully',
            'data' => $application
        ]);
    }
}
