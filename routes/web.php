<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\UploadController;
use Illuminate\Support\Facades\Route;

// ============================================================
// GUEST ROUTES (No Auth)
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ============================================================
// VIEWER ROUTES (Any logged-in user)
// ============================================================
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::get('/', fn() => redirect()->route('dashboard'))->name('home');
    
    // Projects - View Only
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    
    // References - View Only
    Route::get('/projects/{project}/references', [ReferenceController::class, 'forProject'])
        ->name('projects.references');
    
    // Sessions - View Only
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
    
    // AI Outputs - View Only
    Route::get('/ai/outputs/{session}', [AIController::class, 'outputs'])->name('ai.outputs');
    Route::get('/ai/outputs/{session}/status/{output}', [AIController::class, 'status'])->name('ai.status');
});


// ============================================================
// EDITOR ROUTES (editor + admin)
// ============================================================
Route::middleware(['auth', 'role:editor'])->group(function () {
    
    // Projects - Create
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    
    // Projects - Edit
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    
    // Projects - Archive
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    
    // References - Manage
    Route::post('/projects/{project}/references/primary', [ReferenceController::class, 'setPrimary'])
        ->name('projects.references.primary');
    
    // Sessions - Create
    Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    
    // Uploads
    Route::post('/upload/project-style', [UploadController::class, 'projectStyle'])->name('upload.project-style');
    Route::post('/upload/reference', [UploadController::class, 'reference'])->name('upload.reference');
    Route::delete('/upload/reference/{id}', [UploadController::class, 'deleteReference'])->name('upload.reference.delete');
    
    // AI Generation
    Route::post('/ai/generate/text', [AIController::class, 'generateText'])->name('ai.generate.text');
    Route::post('/ai/generate/image', [AIController::class, 'generateImage'])->name('ai.generate.image');
});


// ============================================================
// ADMIN ROUTES (admin only)
// ============================================================
Route::middleware(['auth', 'role:admin'])->group(function () {
    
    // Project Management - Delete
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});
