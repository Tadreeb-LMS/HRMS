<?php

namespace Modules\HrmsIntegrationModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\HrmsIntegrationModule\Models\HrmsClientConfig;
use Illuminate\Support\Facades\Log;

class HrmsProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $clientConfig;
    public $webhookData;

    /**
     * Create a new job instance.
     */
    public function __construct(HrmsClientConfig $clientConfig, array $webhookData)
    {
        $this->clientConfig = $clientConfig;
        $this->webhookData = $webhookData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = $this->clientConfig->webhook_url;

        try {
            // Build dynamic headers based on provider credentials
            $headers = ['Accept' => 'application/json'];
            
            // Add Authorization header if API Key exists in credentials
            $credentials = $this->clientConfig->provider_credentials ?? [];
            if (isset($credentials['api_key'])) {
                $headers['Authorization'] = 'Bearer ' . $credentials['api_key'];
            }

            $response = Http::withHeaders($headers)
                            ->timeout(30)
                            ->post($url, $this->webhookData);

            if ($response->successful()) {
                Log::info("HRMS Webhook Success [Client {$this->clientConfig->id}]: " . $response->status());
                
                $this->clientConfig->syncLogs()->create([
                    'action' => 'webhook.sent',
                    'status' => 'success',
                    'message' => "Webhook {$this->webhookData['event']} delivered HTTP " . $response->status(),
                    'payload' => $this->webhookData
                ]);
            } else {
                Log::error("HRMS Webhook Failed [Client {$this->clientConfig->id}]: " . $response->status());
                
                $this->clientConfig->syncLogs()->create([
                    'action' => 'webhook.sent',
                    'status' => 'failed',
                    'message' => "Webhook failed HTTP " . $response->status() . " Body: " . $response->body(),
                    'payload' => $this->webhookData
                ]);

                // Can throw exception to rely on Laravel's queued job retries
                $this->fail(new \Exception("Webhook failed with status " . $response->status()));
            }

        } catch (\Exception $e) {
            Log::error("HRMS Webhook Exception [Client {$this->clientConfig->id}]: " . $e->getMessage());
            
            $this->clientConfig->syncLogs()->create([
                'action' => 'webhook.sent',
                'status' => 'failed',
                'message' => "Webhook exception: " . $e->getMessage(),
                'payload' => $this->webhookData
            ]);

            throw $e; // Triggers retry mechanism
        }
    }
}
