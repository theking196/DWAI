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

// Legacy HTML template redirect
Route::get('/projects-html', function () {
    return file_get_contents(base_path('../dwai-studio/index.html'));
});