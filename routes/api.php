<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\SessionController as ApiSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DWAI Studio API Routes
|--------------------------------------------------------------------------
| 
| Local-only API for session management and production tracking.
| All routes require authentication.
|
*/

Route::middleware('auth')->group(function () {
    
    // Session Summary
    Route::get('/sessions/{session}/summary', [ApiSessionController::class, 'summary'])
        ->name('api.sessions.summary');
    
    // Session Memory
    Route::get('/sessions/{session}/memory', [ApiSessionController::class, 'memory'])
        ->name('api.sessions.memory');
    
    Route::put('/sessions/{session}/memory', [ApiSessionController::class, 'updateMemory'])
        ->name('api.sessions.memory.update');
    
    Route::delete('/sessions/{session}/memory', [ApiSessionController::class, 'clearMemory'])
        ->name('api.sessions.memory.clear');
    
    // Session Outputs
    Route::get('/sessions/{session}/outputs', [ApiSessionController::class, 'outputs'])
        ->name('api.sessions.outputs');
    
    // Session Errors
    Route::get('/sessions/{session}/errors', [ApiSessionController::class, 'errors'])
        ->name('api.sessions.errors');
    
    // Session References
    Route::post('/sessions/{session}/references', [ApiSessionController::class, 'addReference'])
        ->name('api.sessions.references.add');
});


// Session Search
Route::get('/sessions/search', function (Illuminate\Http\Request $request) {
    $query = $request->get('q', '');
    $sessions = \App\Models\Session::search($query)->with('project')->paginate(20);
    return $sessions;
})->name('api.sessions.search');



use App\Http\Controllers\Api\CanonController;

Route::get('/canon/{project}/organized', [CanonController::class, 'organized'])->name('api.canon.organized');
Route::get('/canon/{project}/type/{type}', [CanonController::class, 'byType'])->name('api.canon.byType');
Route::get('/canon/{project}/importance/{level}', [CanonController::class, 'byImportance'])->name('api.canon.byImportance');
Route::get('/canon/search', [CanonController::class, 'search'])->name('api.canon.search');


// Canon Search
Route::get('/canon/search', function (Illuminate\Http\Request $request) {
    $params = [
        'project_id' => $request->get('project_id'),
        'keyword' => $request->get('keyword'),
        'type' => $request->get('type'),
        'types' => $request->get('types') ? explode(',', $request->get('types')) : null,
        'importance' => $request->get('importance'),
        'tag' => $request->get('tag'),
        'tags' => $request->get('tags') ? explode(',', $request->get('tags')) : null,
        'from_date' => $request->get('from_date'),
        'to_date' => $request->get('to_date'),
        'sort_by' => $request->get('sort_by', 'created_at'),
        'sort_dir' => $request->get('sort_dir', 'desc'),
    ];

    $results = \App\Models\CanonEntry::searchWithHighlights($params, $request->get('per_page', 20));
    return response()->json($results);
})->name('api.canon.search-full');

// Project tags
Route::get('/canon/{project}/tags', function (int $project) {
    $tags = \App\Models\CanonEntry::getProjectTags($project);
    return response()->json(['tags' => $tags]);
})->name('api.canon.tags');


use App\Http\Controllers\Api\CanonCandidateController;

Route::get('/canon-candidates/{project}', [CanonCandidateController::class, 'index'])->name('api.canon-candidates.index');
Route::post('/canon-candidates', [CanonCandidateController::class, 'store'])->name('api.canon-candidates.store');
Route::get('/canon-candidates/{id}', [CanonCandidateController::class, 'show'])->name('api.canon-candidates.show');
Route::put('/canon-candidates/{id}', [CanonCandidateController::class, 'update'])->name('api.canon-candidates.update');
Route::post('/canon-candidates/{id}/approve', [CanonCandidateController::class, 'approve'])->name('api.canon-candidates.approve');
Route::post('/canon-candidates/{id}/edit-approve', [CanonCandidateController::class, 'editAndApprove'])->name('api.canon-candidates.edit-approve');
Route::post('/canon-candidates/{id}/reject', [CanonCandidateController::class, 'reject'])->name('api.canon-candidates.reject');
Route::post('/canon-candidates/from-session/{sessionId}', [CanonCandidateController::class, 'createFromSession'])->name('api.canon-candidates.from-session');
Route::get('/canon-candidates/{project}/stats', [CanonCandidateController::class, 'stats'])->name('api.canon-candidates.stats');



use App\Http\Controllers\Api\ReferenceImageController;

Route::get('/references', [ReferenceImageController::class, 'index'])->name('api.references.index');
Route::post('/references', [ReferenceImageController::class, 'store'])->name('api.references.store');
Route::put('/references/{id}', [ReferenceImageController::class, 'update'])->name('api.references.update');
Route::post('/references/{id}/primary', [ReferenceImageController::class, 'setPrimary'])->name('api.references.primary');
Route::delete('/references/{id}', [ReferenceImageController::class, 'destroy'])->name('api.references.destroy');
Route::get('/references/project/{project}/count', [ReferenceImageController::class, 'countByProject'])->name('api.references.count');

