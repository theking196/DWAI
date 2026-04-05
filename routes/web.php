<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return view('pages.dashboard');
})->name('dashboard');

Route::resource('projects', ProjectController::class);
Route::resource('sessions', SessionController::class);

// Legacy route aliases
Route::get('/project/{id}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/session/{id}', [SessionController::class, 'show'])->name('sessions.show');