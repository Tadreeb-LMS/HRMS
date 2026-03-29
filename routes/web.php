<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'role:administrator']], function () {
    Route::get('external-apps/hrms/settings', 'Controllers\Backend\HrmsSettingsController@index')->name('admin.hrms.settings');
    Route::get('external-apps/hrms/configure/{slug}', 'Controllers\Backend\HrmsSettingsController@configure')->name('admin.hrms.configure');
    Route::post('external-apps/hrms/store/{slug}', 'Controllers\Backend\HrmsSettingsController@store')->name('admin.hrms.store');
    Route::post('external-apps/hrms/toggle/{slug}', 'Controllers\Backend\HrmsSettingsController@toggle')->name('admin.hrms.toggle');
    Route::post('external-apps/hrms/sync/{slug}', 'Controllers\Backend\HrmsSettingsController@sync')->name('admin.hrms.sync');
    
    // Zoho OAuth routes
    Route::get('external-apps/hrms/zoho/oauth/redirect', 'Controllers\Backend\HrmsZohoOAuthController@redirect')->name('admin.hrms.zoho.redirect');
    Route::get('external-apps/hrms/zoho/oauth/callback', 'Controllers\Backend\HrmsZohoOAuthController@callback')->name('admin.hrms.zoho.callback');
});
