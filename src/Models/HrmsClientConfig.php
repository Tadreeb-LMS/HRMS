<?php

namespace Modules\HrmsIntegrationModule\Models;

use Illuminate\Database\Eloquent\Model;

class HrmsClientConfig extends Model
{
    protected $table = 'hrms_client_configs';
    
    protected $fillable = [
        'client_name',
        'api_key',
        'hrms_provider',
        'provider_credentials',
        'field_mappings',
        'webhook_url',
        'is_active',
    ];

    protected $casts = [
        'provider_credentials' => 'array',
        'field_mappings' => 'array',
        'is_active' => 'boolean',
    ];

    public function rules()
    {
        return $this->hasMany(HrmsAssignmentRule::class, 'hrms_client_config_id');
    }

    public function syncLogs()
    {
        return $this->hasMany(HrmsSyncLog::class, 'hrms_client_config_id');
    }
}
