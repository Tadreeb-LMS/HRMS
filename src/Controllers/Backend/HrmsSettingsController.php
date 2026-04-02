<?php

namespace Modules\HrmsIntegrationModule\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HrmsSettingsController extends Controller
{
    private function getProviders()
    {
        return [
            'zoho' => [
                'name' => 'Zoho People',
                'description' => 'Sync users and trigger course assignments via Zoho\'s HR platform.',
                'icon' => 'fas fa-building',
            ],
            'sap' => [
                'name' => 'SAP SuccessFactors',
                'description' => 'Connect seamlessly with SAP SuccessFactors environments.',
                'icon' => 'fas fa-server',
            ],
            'darwinbox' => [
                'name' => 'Darwinbox',
                'description' => 'Set up data mapping and authentication for Darwinbox APIs.',
                'icon' => 'fas fa-box',
            ],
            'custom' => [
                'name' => 'Custom Integration Hub',
                'description' => 'Use standard Bearer tokens if connecting a custom-built HR system.',
                'icon' => 'fas fa-plug',
            ],
        ];
    }

    /**
     * Show all HRMS providers with status and actions.
     */
    public function index()
    {
        $settings = Config::pluck('value', 'key')->toArray();
        $providers = $this->getProviders();

        foreach ($providers as $slug => &$provider) {
            $provider['enabled'] = filter_var($settings["hrms_{$slug}_active"] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            // Check credentials exist
            if ($slug === 'zoho') {
                $provider['has_credentials'] = !empty($settings['hrms_zoho_client_id']) && !empty($settings['hrms_zoho_client_secret']);
            } elseif ($slug === 'sap') {
                $provider['has_credentials'] = !empty($settings['hrms_sap_api_key']) && !empty($settings['hrms_sap_company_id']);
            } elseif ($slug === 'darwinbox') {
                $provider['has_credentials'] = !empty($settings['hrms_darwinbox_api_token']);
            } else {
                $provider['has_credentials'] = !empty($settings['hrms_custom_bearer_token']);
            }
        }

        $viewPath = base_path('modules/hrms-integration-module/resources/views/backend/settings/index.blade.php');
        return View::file($viewPath, compact('providers'));
    }

    /**
     * Show configuration form for a single HRMS provider.
     */
    public function configure(Request $request, string $slug)
    {
        $providers = $this->getProviders();
        if (!isset($providers[$slug])) {
            abort(404, 'Provider not found.');
        }

        $provider = $providers[$slug];
        $settings = Config::pluck('value', 'key')->toArray();

        $viewPath = base_path('modules/hrms-integration-module/resources/views/backend/settings/configure.blade.php');
        return View::file($viewPath, compact('provider', 'slug', 'settings'));
    }

    /**
     * Save config for a single provider.
     */
    public function store(Request $request, string $slug)
    {
        $providers = $this->getProviders();
        if (!isset($providers[$slug])) {
            abort(404, 'Provider not found.');
        }

        $inputData = $request->except(['_token', '_method']);

        foreach ($inputData as $key => $value) {
            // Only save keys that relate to the specific slug to prevent cross-contamination
            if(strpos($key, "hrms_{$slug}_") === 0 || $key === "hrms_{$slug}_active") {
                Config::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '']
                );
            }
        }

        return redirect()->route('admin.hrms.configure', ['slug' => $slug])
            ->with('success', $providers[$slug]['name'] . ' configuration saved successfully.');
    }

    /**
     * Toggle provider enabled/disabled state via AJAX.
     */
    public function toggle(Request $request, string $slug)
    {
        $providers = $this->getProviders();
        if (!isset($providers[$slug])) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $key = "hrms_{$slug}_active";
        $currentConfig = Config::where('key', $key)->first();
        $currentlyEnabled = $currentConfig ? filter_var($currentConfig->value, FILTER_VALIDATE_BOOLEAN) : false;
        $newState = !$currentlyEnabled;

        if ($newState) {
            $settings = Config::pluck('value', 'key')->toArray();
            $testResult = $this->testConnection($slug, $settings);
            
            if (!$testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot enable provider. Configuration test failed: ' . $testResult['message']
                ], 400);
            }
        }

        Config::updateOrCreate(
            ['key' => $key],
            ['value' => $newState ? 1 : 0]
        );

        return response()->json([
            'success' => true,
            'enabled' => $newState,
            'message' => $providers[$slug]['name'] . ($newState ? ' enabled' : ' disabled') . ' successfully.',
        ]);
    }

    private function getZohoAccessToken($settings)
    {
        $clientId = $settings['hrms_zoho_client_id'] ?? '';
        $clientSecret = $settings['hrms_zoho_client_secret'] ?? '';
        $refreshToken = $settings['hrms_zoho_refresh_token'] ?? '';
        $domain = $settings['hrms_zoho_api_domain'] ?? 'zoho.com';

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return null;
        }

        try {
            $tokenUrl = "https://accounts.{$domain}/oauth/v2/token";
            $response = Http::asForm()->post($tokenUrl, [
                'refresh_token' => $refreshToken,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'grant_type'    => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Zoho Access Token Fetch Error', ['msg' => $e->getMessage()]);
        }
        return null;
    }

    private function testConnection($slug, $settings)
    {
        if ($slug === 'zoho') {
            $clientId = $settings['hrms_zoho_client_id'] ?? '';
            $clientSecret = $settings['hrms_zoho_client_secret'] ?? '';
            $refreshToken = $settings['hrms_zoho_refresh_token'] ?? '';
            $domain = $settings['hrms_zoho_api_domain'] ?? 'zoho.com';

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
                return ['success' => false, 'message' => 'Credentials or Refresh Token not fully configured.'];
            }

            // Generate short-lived access_token
            $accessToken = $this->getZohoAccessToken($settings);
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Failed to generate access token from refresh token.'];
            }

            try {
                $url = "https://people.{$domain}/api/forms/employee/getRecords?limit=1";
                $response = Http::withHeaders([
                    'Authorization' => 'Zoho-oauthtoken ' . $accessToken
                ])->timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['response']['errors'])) {
                        return ['success' => false, 'message' => 'API Error: ' . json_encode($data['response']['errors'])];
                    }
                    return ['success' => true, 'message' => 'Connection OK.'];
                }
                
                return ['success' => false, 'message' => 'HTTP Error: ' . $response->status()];

            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'HTTP Exception: ' . $e->getMessage()];
            }
        }
        
        // Simple fallback checks for other providers
        if ($slug === 'sap') {
            return !empty($settings['hrms_sap_api_key']) && !empty($settings['hrms_sap_company_id']) 
                ? ['success' => true, 'message' => 'OK'] : ['success' => false, 'message' => 'Credentials not set.'];
        }
        if ($slug === 'darwinbox') {
            return !empty($settings['hrms_darwinbox_api_token']) 
                ? ['success' => true, 'message' => 'OK'] : ['success' => false, 'message' => 'Credentials not set.'];
        }
        if ($slug === 'custom') {
            return !empty($settings['hrms_custom_bearer_token']) 
                ? ['success' => true, 'message' => 'OK'] : ['success' => false, 'message' => 'Credentials not set.'];
        }

        return ['success' => false, 'message' => 'Unknown provider format.'];
    }

    /**
     * Sync employees from HRMS provider.
     */
    public function sync(Request $request, string $slug)
    {
        $providers = $this->getProviders();
        if (!isset($providers[$slug])) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $settings = Config::pluck('value', 'key')->toArray();
        $enabled = filter_var($settings["hrms_{$slug}_active"] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enabled) {
            return response()->json(['message' => 'Provider is currently disabled.'], 400);
        }

        if ($slug === 'zoho') {
            return $this->syncZoho($settings);
        }

        return response()->json(['message' => "Sync is not implemented for {$providers[$slug]['name']} yet."], 400);
    }

    private function syncZoho($settings)
    {
        $clientId = $settings['hrms_zoho_client_id'] ?? '';
        $clientSecret = $settings['hrms_zoho_client_secret'] ?? '';
        $refreshToken = $settings['hrms_zoho_refresh_token'] ?? '';
        $domain = $settings['hrms_zoho_api_domain'] ?? 'zoho.com';

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return response()->json(['message' => 'Zoho configuration not fully completed.'], 400);
        }

        try {
            $accessToken = $this->getZohoAccessToken($settings);
            if (!$accessToken) {
                return response()->json(['message' => 'Could not generate access token. Try re-authenticating.'], 400);
            }

            $url = "https://people.{$domain}/api/forms/employee/getRecords";

            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken
            ])->get($url);
            
            $employees = [];
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['response']['result']) && is_array($data['response']['result'])) {
                    foreach($data['response']['result'] as $record) {
                        $employees[] = isset($record[0]) ? $record[0] : $record;
                    }
                }
            } else {
                return response()->json(['message' => 'Failed to fetch from Zoho API: ' . $response->status()], 400);
            }

            if (empty($employees)) {
                return response()->json(['message' => 'No employees found or empty API response.'], 200);
            }

            $syncedCount = 0;
            foreach ($employees as $emp) {
                $email = $emp['EmailID'] ?? ($emp['Email'] ?? null);
                $zohoId = $emp['EmployeeID'] ?? ($emp['Zoho_ID'] ?? null);

                if (!$email && !$zohoId) continue;
                
                $firstName = $emp['FirstName'] ?? 'Unknown';
                $lastName = $emp['LastName'] ?? '';

                $user = null;
                if ($zohoId) {
                    $user = User::where('hrms_provider', 'zoho')->where('hrms_id', $zohoId)->first();
                }
                
                if (!$user && $email) {
                    $user = User::where('email', $email)->first();
                }

                if ($user) {
                    $user->update([
                        'hrms_provider' => 'zoho',
                        'hrms_id' => $zohoId,
                    ]);
                } else {
                    $user = User::create([
                        'email' => $email ? $email : 'zoho_' . $zohoId . '@example.com',
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'name' => trim($firstName . ' ' . $lastName),
                        'hrms_provider' => 'zoho',
                        'hrms_id' => $zohoId,
                        'password' => bcrypt(Str::random(16)),
                    ]);
                    
                    if ($user->wasRecentlyCreated) {
                        $user->assignRole('student');
                    }
                }
                
                $syncedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$syncedCount} employees from Zoho."
            ]);

        } catch (\Exception $e) {
            Log::error('Zoho Sync Error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during sync: ' . $e->getMessage()], 500);
        }
    }
}
