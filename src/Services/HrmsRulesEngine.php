<?php

namespace Modules\HrmsIntegrationModule\Services;

use App\Models\Auth\User;
use Modules\HrmsIntegrationModule\Models\HrmsClientConfig;
use Illuminate\Support\Facades\Log;

class HrmsRulesEngine
{
    /**
     * Evaluate HRMS rules to auto-assign courses to a user.
     * 
     * @param User $user
     * @param array $payload The raw HRMS payload (contains fields like department, position)
     * @param HrmsClientConfig $clientConfig
     */
    public function evaluateRules(User $user, array $payload, HrmsClientConfig $clientConfig)
    {
        $rules = $clientConfig->rules()->where('is_active', true)->get();

        foreach ($rules as $rule) {
            $field = $rule->condition_field;
            $operator = $rule->condition_operator;
            $expectedValue = $rule->condition_value;

            $actualValue = $payload[$field] ?? null;

            if ($this->matchCondition($actualValue, $operator, $expectedValue)) {
                $this->assignCourse($user, $rule->course_id, $clientConfig);
            }
        }
    }

    protected function matchCondition($actual, $operator, $expected)
    {
        if (is_null($actual)) {
            return false;
        }

        switch ($operator) {
            case '=':
            case '==':
                return strcasecmp($actual, $expected) === 0;
            case '!=':
                return strcasecmp($actual, $expected) !== 0;
            case '>':
                return $actual > $expected;
            case '<':
                return $actual < $expected;
            case 'contains':
                return stripos($actual, $expected) !== false;
            default:
                return false;
        }
    }

    protected function assignCourse(User $user, $course_id, HrmsClientConfig $clientConfig)
    {
        $course = \App\Models\Course::find($course_id);
        if (!$course) {
            return;
        }

        if (!$course->students()->where('user_id', $user->id)->exists()) {
            $course->students()->attach($user->id);
            Log::info("HRMS Rule Engine: Assigned course '{$course->title}' to user {$user->email}");
            
            // Log sync action
            $clientConfig->syncLogs()->create([
                'action' => 'course.assigned',
                'status' => 'success',
                'message' => "Auto-assigned course ID {$course_id} via rule engine.",
                'payload' => ['user_id' => $user->id, 'course_id' => $course_id]
            ]);
        }
    }
}
