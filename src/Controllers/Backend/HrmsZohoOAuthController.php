<?php

namespace Modules\HrmsIntegrationModule\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrmsZohoOAuthController extends Controller
{
    /**
     * Redirect admin to Zoho OAuth 2.0 URL
     */
    public function redirect(Request $request)
    {
        $settings = Config::pluck('value', 'key')->toArray();
        $clientId = $settings['hrms_zoho_client_id'] ?? null;
        $domain = $settings['hrms_zoho_api_domain'] ?? 'zoho.com';

        if (!$clientId) {
            return redirect()->route('admin.hrms.configure', ['slug' => 'zoho'])
                ->with('error', 'Please configure the Client ID first and save.');
        }

        // ngrok compatibility note: if running locally, APP_URL in .env must match the ngrok URL
        // Example: https://abc123.ngrok-free.app 
        $redirectUri = route('admin.hrms.zoho.callback');
        $scope = 'ZohoPeople.employee.ALL,ZohoPeople.forms.ALL';

        $authUrl = "https://accounts.{$domain}/oauth/v2/auth?" . http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'access_type'   => 'offline',
            'redirect_uri'  => $redirectUri,
            'scope'         => $scope,
            'prompt'        => 'consent'
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Handle the callback from Zoho OAuth and exchange the code for a token
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            return redirect()->route('admin.hrms.configure', ['slug' => 'zoho'])
                ->with('error', "Zoho OAuth Error: {$error}");
        }

        if (!$code) {
            return redirect()->route('admin.hrms.configure', ['slug' => 'zoho'])
                ->with('error', 'No authorization code received from Zoho.');
        }

        $settings = Config::pluck('value', 'key')->toArray();
        $clientId = $settings['hrms_zoho_client_id'] ?? '';
        $clientSecret = $settings['hrms_zoho_client_secret'] ?? '';
        $domain = $settings['hrms_zoho_api_domain'] ?? 'zoho.com';
        $redirectUri = route('admin.hrms.zoho.callback');

        try {
            $tokenUrl = "https://accounts.{$domain}/oauth/v2/token";
            $response = Http::asForm()->post($tokenUrl, [
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['error'])) {
                    return redirect()->route('admin.hrms.configure', ['slug' => 'zoho', 'oauth_error' => 'Token exchange failed: ' . $data['error']]);
                }

                if (isset($data['refresh_token'])) {
                    Config::updateOrCreate(
                        ['key' => 'hrms_zoho_refresh_token'],
                        ['value' => $data['refresh_token']]
                    );
                    
                    return redirect()->route('admin.hrms.configure', ['slug' => 'zoho', 'oauth_success' => '1']);
                }

                return redirect()->route('admin.hrms.configure', ['slug' => 'zoho', 'oauth_error' => 'Authentication succeeded but no refresh token received. (Ensure offline access type and prompt=consent)']);
            }

            return redirect()->route('admin.hrms.configure', ['slug' => 'zoho', 'oauth_error' => 'Failed to retrieve tokens. HTTP Status: ' . $response->status()]);

        } catch (\Exception $e) {
            Log::error('Zoho Token Exchange Error', ['message' => $e->getMessage()]);
            return redirect()->route('admin.hrms.configure', ['slug' => 'zoho', 'oauth_error' => 'Exception during authentication: ' . $e->getMessage()]);
        }
    }
}
