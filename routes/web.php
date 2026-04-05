<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\UploadController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (auth required)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    
    // References
    Route::get('/projects/{project}/references', [ReferenceController::class, 'forProject'])->name('projects.references');
    Route::post('/projects/{project}/references/primary', [ReferenceController::class, 'setPrimary'])->name('projects.references.primary');
    
    // Sessions
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    
    // Uploads
    Route::post('/upload/project-style', [UploadController::class, 'projectStyle'])->name('upload.project-style');
    Route::post('/upload/reference', [UploadController::class, 'reference'])->name('upload.reference');
    Route::delete('/upload/reference/{id}', [UploadController::class, 'deleteReference'])->name('upload.reference.delete');
    
    // AI
    Route::post('/ai/generate/text', [AIController::class, 'generateText'])->name('ai.generate.text');
    Route::post('/ai/generate/image', [AIController::class, 'generateImage'])->name('ai.generate.image');
    Route::get('/ai/outputs/{session}', [AIController::class, 'outputs'])->name('ai.outputs');
    Route::get('/ai/outputs/{session}/status/{output}', [AIController::class, 'status'])->name('ai.status');
});

// Home - redirect to login if not authenticated, else dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');
