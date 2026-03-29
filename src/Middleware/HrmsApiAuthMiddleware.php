<?php

namespace Modules\HrmsIntegrationModule\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\HrmsIntegrationModule\Models\HrmsClientConfig;

class HrmsApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Bearer token required.'
            ], 401);
        }

        $clientConfig = HrmsClientConfig::where('api_key', $token)
            ->where('is_active', true)
            ->first();

        if (!$clientConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or inactive token.'
            ], 403);
        }

        // Attach client config to request for later use
        $request->merge(['hrms_client' => $clientConfig]);

        return $next($request);
    }
}
