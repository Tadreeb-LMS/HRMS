<?php

namespace Modules\HrmsIntegrationModule\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use App\Models\Course;

class HrmsReportController extends Controller
{
    /**
     * Get user progress report
     */
    public function userProgress(Request $request, $employee_id)
    {
        // First find user by employee ID/email mapping
        // In TadreebLMS, there's no native employee_id in User model unless added. 
        // Assuming employee_id is passed as search query or mapped.
        
        $user = User::where('email', $employee_id)
                    ->orWhere('id', $employee_id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $progress = [];
        
        // Loop through assigned courses and get progress
        foreach ($user->courses as $course) {
            $courseProgress = $user->chapterStudents()->where('course_id', $course->id)->count();
            $totalContent = $course->courseTimeline()->count();
            
            $percentage = $totalContent > 0 ? round(($courseProgress / $totalContent) * 100, 2) : 0;

            $progress[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'progress_percentage' => $percentage,
                'completed' => $percentage == 100
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email
                ],
                'progress' => $progress
            ]
        ]);
    }

    /**
     * Get department progress report
     */
    public function departmentProgress(Request $request, $id)
    {
        // Sample implementation for department stats
        // Needs proper department linkage depending on TadreebLMS schema
        return response()->json([
            'success' => true,
            'message' => 'Department reporting endpoint',
            'data' => []
        ]);
    }
}
