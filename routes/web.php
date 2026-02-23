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



    Route::get   ('/users',           [VoyagerUserController::class, 'index']  )->name('users.index');
    Route::get   ('users/create',    [VoyagerUserController::class, 'create'] )->name('users.create');
    Route::post  ('users',           [VoyagerUserController::class, 'store']  )->name('users.store');
    Route::get   ('users/{id}/edit', [VoyagerUserController::class, 'edit']   )->name('users.edit');
    Route::put   ('users/{id}',      [VoyagerUserController::class, 'update'] )->name('users.update');
    Route::patch ('users/{id}',      [VoyagerUserController::class, 'update'] )->name('users.update_patch');
    Route::delete('users/{id}',      [VoyagerUserController::class, 'destroy'])->name('users.destroy');



    Route::post('users/remove-file', [
        UserFileController::class,
        'removeFile',
    ])->name('users.remove-file');

    Voyager::routes();
});


