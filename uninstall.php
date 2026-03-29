<?php

use Illuminate\Support\Facades\Log;

try {
    Log::info('HRMS Module Uninstaller running.');
    // Any cleanup like dropping tables if necessary.
} catch (\Exception $e) {
    Log::error('HRMS Module Uninstall Error: ' . $e->getMessage());
}
