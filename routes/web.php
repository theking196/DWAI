<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\UploadController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (any authenticated user)
Route::middleware('auth')->group(function () {
    // Dashboard - viewer+
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    
    // Projects - viewer+
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    
    // Project deletion - admin only
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy')->middleware('role:admin');
    
    // References - viewer+
    Route::get('/projects/{project}/references', [ReferenceController::class, 'forProject'])->name('projects.references');
    Route::post('/projects/{project}/references/primary', [ReferenceController::class, 'setPrimary'])->name('projects.references.primary')->middleware('role:editor');
    
    // Sessions - viewer+
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    
    // Uploads - editor+
    Route::post('/upload/project-style', [UploadController::class, 'projectStyle'])->name('upload.project-style')->middleware('role:editor');
    Route::post('/upload/reference', [UploadController::class, 'reference'])->name('upload.reference')->middleware('role:editor');
    Route::delete('/upload/reference/{id}', [UploadController::class, 'deleteReference'])->name('upload.reference.delete')->middleware('role:editor');
    
    // AI generation - editor+
    Route::post('/ai/generate/text', [AIController::class, 'generateText'])->name('ai.generate.text')->middleware('role:editor');
    Route::post('/ai/generate/image', [AIController::class, 'generateImage'])->name('ai.generate.image')->middleware('role:editor');
    Route::get('/ai/outputs/{session}', [AIController::class, 'outputs'])->name('ai.outputs')->middleware('role:viewer');
    Route::get('/ai/outputs/{session}/status/{output}', [AIController::class, 'status'])->name('ai.status')->middleware('role:viewer');
});

// Home
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');
