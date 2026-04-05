<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', fn() => redirect()->route('dashboard'))->name('home');

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
Route::post('/upload/session-output', [UploadController::class, 'sessionOutput'])->name('upload.session-output');

// AI
Route::post('/ai/generate/text', [AIController::class, 'generateText'])->name('ai.generate.text');
Route::post('/ai/generate/image', [AIController::class, 'generateImage'])->name('ai.generate.image');
Route::get('/ai/outputs/{session}', [AIController::class, 'outputs'])->name('ai.outputs');
Route::get('/ai/outputs/{session}/status/{output}', [AIController::class, 'status'])->name('ai.status');
Route::get('/ai/providers', [AIController::class, 'providers'])->name('ai.providers');
