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
