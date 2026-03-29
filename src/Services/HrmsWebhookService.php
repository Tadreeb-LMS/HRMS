<?php

namespace Modules\HrmsIntegrationModule\Services;

use Modules\HrmsIntegrationModule\Models\HrmsClientConfig;
use Modules\HrmsIntegrationModule\Jobs\HrmsProcessWebhookJob;
use Illuminate\Support\Facades\Log;

class HrmsWebhookService
{
    /**
     * Dispatch webhook to all active HRMS clients that have webhooks configured.
     * 
     * @param string $event The event name e.g. 'course.completed'
     * @param array $payload The payload data
     */
    public function dispatchEvent($event, $payload)
    {
        $clients = HrmsClientConfig::where('is_active', true)
            ->whereNotNull('webhook_url')
            ->get();

        foreach ($clients as $client) {
            $webhookData = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'payload' => $payload
            ];

            // Dispatch job to handle the HTTP request asynchronously to avoid blocking
            HrmsProcessWebhookJob::dispatch($client, $webhookData);
            
            Log::info("HRMS WebhookService: Queued {$event} webhook for client ID {$client->id}");
        }
    }
}
