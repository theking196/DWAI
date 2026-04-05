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



use App\Http\Controllers\Api\AssetController;

Route::get('/assets', [AssetController::class, 'index'])->name('api.assets.index');
Route::post('/assets', [AssetController::class, 'store'])->name('api.assets.store');
Route::get('/assets/{id}', [AssetController::class, 'show'])->name('api.assets.show');
Route::put('/assets/{id}', [AssetController::class, 'update'])->name('api.assets.update');
Route::delete('/assets/{id}', [AssetController::class, 'destroy'])->name('api.assets.destroy');
Route::get('/assets/{project}/organized', [AssetController::class, 'organized'])->name('api.assets.organized');
Route::get('/assets/{project}/stats', [AssetController::class, 'stats'])->name('api.assets.stats');
Route::post('/assets/{id}/tag', [AssetController::class, 'addTag'])->name('api.assets.add-tag');
Route::get('/assets/{project}/tags', [AssetController::class, 'projectTags'])->name('api.assets.project-tags');



Route::post('/projects/{project}/style-image', function (Illuminate\Http\Request $request, int $project) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $request->validate(['image' => 'required|image|max:10240', 'title' => 'nullable|string|max:255']);
    $path = $request->file('image')->store('styles/' . $project->id, 'public');
    $project->setStyleImage($path, $request->title);
    
    return response()->json(['url' => $project->style_image_url]);
})->name('api.projects.style-image');

Route::post('/projects/{project}/style-images', function (Illuminate\Http\Request $request, int $project) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $request->validate(['image' => 'required|image|max:10240', 'title' => 'nullable|string|max:255']);
    $path = $request->file('image')->store('styles/' . $project->id, 'public');
    $project->addStyleImage($path, $request->title);
    
    return response()->json(['images' => $project->getStyleImagesWithUrls()]);
})->name('api.projects.style-images-add');

Route::delete('/projects/{project}/style-images/{index}', function (int $project, int $index) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $project->removeStyleImage($index);
    return response()->json(['images' => $project->getStyleImagesWithUrls()]);
})->name('api.projects.style-images-remove');

Route::put('/projects/{project}/style-notes', function (Illuminate\Http\Request $request, int $project) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $project->setStyleNotes($request->notes);
    return response()->json(['style_notes' => $project->style_notes]);
})->name('api.projects.style-notes');

Route::get('/projects/{project}/style', function (int $project) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    return response()->json([
        'main_image' => ['url' => $project->style_image_url, 'title' => $project->style_image_title],
        'supporting_images' => $project->getStyleImagesWithUrls(),
        'notes' => $project->style_notes,
    ]);
})->name('api.projects.style');



Route::get('/assets/{id}/preview', function (int $id) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json($asset->getPreview());
})->name('api.assets.preview');

Route::post('/assets/{id}/replace', function (Illuminate\Http\Request $request, int $id) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $request->validate(['file' => 'required|file|max:51200']);
    $asset->replaceFile($request->file('file'));
    return response()->json(['asset' => $asset]);
})->name('api.assets.replace');

Route::post('/assets/{id}/relink', function (Illuminate\Http\Request $request, int $id) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $asset->relink($request->session_id, $request->canon_entry_id);
    return response()->json($asset);
})->name('api.assets.relink');

Route::post('/assets/{id}/relink-project/{projectId}', function (int $id, int $projectId) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $asset->relinkToProject($projectId);
    return response()->json($asset);
})->name('api.assets.relink-project');

Route::post('/assets/{id}/style-reference', function (int $id) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $asset->markAsStyleReference();
    return response()->json(['is_style_reference' => true]);
})->name('api.assets.mark-style');

Route::delete('/assets/{id}/style-reference', function (int $id) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $asset->unmarkAsStyleReference();
    return response()->json(['is_style_reference' => false]);
})->name('api.assets.unmark-style');

Route::post('/assets/{id}/duplicate/{projectId}', function (int $id, int $projectId) {
    $asset = \App\Models\Asset::findOrFail($id);
    if ($asset->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $newAsset = $asset->duplicateTo($projectId);
    return response()->json($newAsset);
})->name('api.assets.duplicate');



Route::get('/sessions/{session}/memory', function (int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json($session->getShortTermMemory());
})->name('api.sessions.memory');

Route::put('/sessions/{session}/memory', function (Illuminate\Http\Request $request, int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    if ($request->has('temp_notes')) $session->updateTempNotes($request->temp_notes);
    if ($request->has('ai_reasoning')) $session->updateAIReasoning($request->ai_reasoning);
    if ($request->has('draft_text')) $session->updateDraftText($request->draft_text);
    return response()->json($session->getShortTermMemory());
})->name('api.sessions.memory.update');

Route::post('/sessions/{session}/memory/reference', function (Illuminate\Http\Request $request, int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $session->addSessionReference(['url' => $request->url, 'title' => $request->title ?? null]);
    return response()->json($session->getShortTermMemory());
})->name('api.sessions.memory.add-reference');

Route::delete('/sessions/{session}/memory', function (int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $session->clearShortTermMemory();
    return response()->json(['cleared' => true]);
})->name('api.sessions.memory.clear');



Route::post('/sessions/{session}/close', function (Illuminate\Http\Request $request, int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $action = $request->get('memory_action', 'discard');  // promote, discard
    $promoteData = $request->only(['title', 'type', 'importance', 'tags']);
    $session->closeWithMemoryAction($action, $promoteData);
    return response()->json(['status' => 'completed', 'action' => $action]);
})->name('api.sessions.close');

Route::get('/sessions/{session}/memory/summary', function (int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json($session->getMemorySummary());
})->name('api.sessions.memory.summary');

Route::post('/sessions/{session}/memory/promote', function (Illuminate\Http\Request $request, int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $options = $request->only(['title', 'type', 'importance', 'tags']);
    $canon = $session->promoteMemoryToCanon($options);
    return response()->json(['promoted' => $canon ? true : false, 'canon_id' => $canon?->id]);
})->name('api.sessions.memory.promote');

