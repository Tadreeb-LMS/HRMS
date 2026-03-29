<?php

use Illuminate\Support\Facades\Route;

Route::post('users', 'HrmsApiController@storeUser')->name('users.store');
Route::get('courses', 'HrmsApiController@getCourses')->name('courses.list');
Route::post('enrollments', 'HrmsApiController@storeEnrollment')->name('enrollments.store');
Route::post('enrollments/bulk', 'HrmsApiController@storeBulkEnrollment')->name('enrollments.bulk_store');

Route::get('reports/user/{employee_id}', 'HrmsReportController@userProgress')->name('reports.user');
Route::get('reports/department/{id}', 'HrmsReportController@departmentProgress')->name('reports.department');
