<?php

namespace Modules\HrmsIntegrationModule\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\HrmsIntegrationModule\Services\HrmsRulesEngine;

class HrmsApiController extends Controller
{
    /**
     * Create or update a user from HRMS payload
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        // Find or create user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(10)),
                'active' => 1,
                'confirmed' => 1,
            ]);
            $user->assignRole('student');
        } else {
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);
        }

        // Evaluate Rules Engine for auto-assignment based on metadata
        $clientConfig = request()->hrms_client;
        if ($clientConfig) {
            $rulesEngine = new HrmsRulesEngine();
            $rulesEngine->evaluateRules($user, $request->all(), $clientConfig);
        }

        // Return user status
        return response()->json([
            'success' => true,
            'message' => 'User synchronized successfully',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'employee_id' => $request->employee_id
            ]
        ]);
    }

    /**
     * Get list of courses available for assignment
     */
    public function getCourses(Request $request)
    {
        $courses = Course::where('published', 1)->get()->map(function($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    /**
     * Assign a course to a user
     */
    public function storeEnrollment(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'course_id' => 'required|exists:courses,id'
        ]);

        $user = User::where('email', $request->email)->first();
        $course = Course::findOrFail($request->course_id);

        if (!$course->students()->where('user_id', $user->id)->exists()) {
            $course->students()->attach($user->id);
            // Fire event if necessary
        }

        return response()->json([
            'success' => true,
            'message' => 'Course assigned successfully'
        ]);
    }

    /**
     * Bulk assign a course to multiple users
     */
    public function storeBulkEnrollment(Request $request)
    {
        $request->validate([
            'emails' => 'required|array',
            'course_id' => 'required|exists:courses,id'
        ]);

        $course = Course::findOrFail($request->course_id);
        $users = User::whereIn('email', $request->emails)->get();

        $enrolled = 0;
        foreach ($users as $user) {
            if (!$course->students()->where('user_id', $user->id)->exists()) {
                $course->students()->attach($user->id);
                $enrolled++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully enrolled {$enrolled} users."
        ]);
    }
}
