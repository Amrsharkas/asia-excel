<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It's a breeze. Simply tell Laravel the URIs it should respond to
  | and give it the controller to call when that URI is requested.
  |
 */

Route::get('/', function () {
    return view('welcome');
});
Route::get('/change_email', function () {
    return view('change_email');
});
Route::get('/new_email', function () {
    return view('new_email');
});
Route::get('reminder', 'reminderController@handle');
Route::post('upload', 'reminderController@showUploadFile');
Route::post('email', 'reminderController@email');
Route::post('new_email', 'reminderController@newEmail');

Route::get('migStudents', 'migController@students');
Route::get('migTeachers', 'migController@teachers');
Route::get('migTSessions', 'migController@tSessions');
Route::get('migSSessions', 'migController@sSessions');

Route::get('courses', 'DataController@courses');
Route::get('course', 'DataController@course');
Route::group(['prefix' => 'migration'], function () {
    Route::get('students', 'MigrationController@students');
    Route::get('try', function() {
        $token = str_random(5) . (microtime(true) * 10000);
        echo url('migration/process/' . $token);
    });
    Route::get('process/{token}', function ($token) {
        $path = public_path('sheets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . $token);
        if (!file_exists($path)) {
            return 'Process not found';
        }
        $status = file_get_contents($path);
        if ($status == 'INIT') {
            return 'Process is still in progress';
        } elseif ($status == 'DONE') {
            return 'Process Completed';
        } else {
            return 'System error';
        }
    });
});
