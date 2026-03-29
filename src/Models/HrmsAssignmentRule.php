<?php

namespace Modules\HrmsIntegrationModule\Models;

use Illuminate\Database\Eloquent\Model;

class HrmsAssignmentRule extends Model
{
    protected $table = 'hrms_assignment_rules';
    
    protected $fillable = [
        'hrms_client_config_id',
        'condition_field',
        'condition_operator',
        'condition_value',
        'course_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function clientConfig()
    {
        return $this->belongsTo(HrmsClientConfig::class, 'hrms_client_config_id');
    }

    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class, 'course_id');
    }
}
