<?php

namespace Modules\HrmsIntegrationModule\Models;

use Illuminate\Database\Eloquent\Model;

class HrmsSyncLog extends Model
{
    protected $table = 'hrms_sync_logs';
    
    protected $fillable = [
        'hrms_client_config_id',
        'action',
        'status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function clientConfig()
    {
        return $this->belongsTo(HrmsClientConfig::class, 'hrms_client_config_id');
    }
}
