<?php

// Check if HRMS module settings route/menu needs insertion, or if database migrations need to run.
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

try {
    Log::info('HRMS Module Installer running.');
    // Run migrations specifically for this module if any.
    // Artisan::call('migrate', ['--path' => 'Modules/HRMS/src/Database/Migrations']);
} catch (\Exception $e) {
    Log::error('HRMS Module Install Error: ' . $e->getMessage());
}
