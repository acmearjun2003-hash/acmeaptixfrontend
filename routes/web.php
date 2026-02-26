<?php

use App\Http\Controllers\Admin\UserFileController;
use App\Http\Controllers\Voyager\VoyagerUserController;
use Illuminate\Support\Facades\Route;
use TCG\Voyager\Facades\Voyager;

Route::get('/', function () {
    return view('welcome');
});

Route::group([
    'prefix' => 'admin'  
], function () {


    Voyager::routes();
    
    Route::post('users/remove-file', [
        UserFileController::class,
        'removeFile',
    ])->name('users.remove-file');
});


