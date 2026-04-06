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



Route::get('/sessions/{session}/memory/promote/form', function (int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json([
        'memory' => $session->getShortTermMemory(),
        'summary' => $session->getMemorySummary(),
        'canon_types' => ['character', 'location', 'lore', 'rule', 'timeline_event', 'note'],
    ]);
})->name('api.sessions.memory.promote-form');

Route::post('/sessions/{session}/memory/promote/review', function (Illuminate\Http\Request $request, int $session) {
    $session = \App\Models\Session::findOrFail($session);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'type' => 'required|in:character,location,lore,rule,timeline_event,note',
        'content' => 'nullable|string',
        'importance' => 'nullable|in:none,minor,important,critical',
        'tags' => 'nullable|array',
    ]);
    
    // Create candidate for review
    $candidate = \App\Models\CanonCandidate::create([
        'user_id' => auth()->id(),
        'project_id' => $session->project_id,
        'session_id' => $session->id,
        'title' => $validated['title'],
        'type' => $validated['type'],
        'content' => $validated['content'] ?? $session->draft_text ?? $session->temp_notes,
        'ai_reasoning' => $session->ai_reasoning,
        'tags' => $validated['tags'] ?? ['from-session'],
        'importance' => $validated['importance'] ?? 'none',
        'status' => 'pending',
    ]);
    
    return response()->json(['candidate_id' => $candidate->id, 'status' => 'pending_review']);
})->name('api.sessions.memory.to-candidate');



Route::get('/canon-candidates/{id}/review', function (int $id) {
    $candidate = \App\Models\CanonCandidate::with('session')->findOrFail($id);
    if ($candidate->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json([
        'candidate' => $candidate,
        'context' => $candidate->getContext(),
    ]);
})->name('api.canon-candidates.review');

Route::post('/canon-candidates/{id}/review/approve', function (Illuminate\Http\Request $request, int $id) {
    $candidate = \App\Models\CanonCandidate::findOrFail($id);
    if ($candidate->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $validated = $request->validate([
        'title' => 'sometimes|string|max:255',
        'type' => 'sometimes|in:character,location,lore,rule,timeline_event,note',
        'content' => 'nullable|string',
        'importance' => 'nullable|in:none,minor,important,critical',
        'tags' => 'nullable|array',
    ]);
    
    // Update candidate with any edits
    $candidate->update(array_filter($validated));
    
    // Approve and promote
    $canon = $candidate->approve($request->notes ?? 'Approved from session review');
    
    return response()->json(['canon_id' => $canon->id, 'status' => 'promoted']);
})->name('api.canon-candidates.review.approve');

Route::post('/canon-candidates/{id}/review/reject', function (Illuminate\Http\Request $request, int $id) {
    $candidate = \App\Models\CanonCandidate::findOrFail($id);
    if ($candidate->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $request->validate(['reason' => 'required|string']);
    $candidate->reject($request->reason);
    
    return response()->json(['status' => 'rejected']);
})->name('api.canon-candidates.review.reject');



Route::get('/canon/{id}/merge/check', function (Illuminate\Http\Request $request, int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $check = $entry->canMergeWith($request->all());
    return response()->json($check);
})->name('api.canon.merge-check');

Route::post('/canon/{id}/merge', function (Illuminate\Http\Request $request, int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $result = $entry->mergeWithHistory($request->all(), $request->reason);
    return response()->json($result);
})->name('api.canon.merge');

Route::post('/canon/{id}/version', function (Illuminate\Http\Request $request, int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $version = $entry->createVersion($request->all(), $request->notes);
    return response()->json(['version_id' => $version->id]);
})->name('api.canon.create-version');

Route::get('/canon/{id}/history', function (int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json(['history' => $entry->getMergeHistory()]);
})->name('api.canon.history');



Route::get('/canon/{id}/versions', function (int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json(['versions' => $entry->getVersionHistory()]);
})->name('api.canon.versions');

Route::post('/canon/{id}/versions', function (Illuminate\Http\Request $request, int $id) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $result = $entry->updateWithVersion($request->all(), $request->summary);
    return response()->json($result);
})->name('api.canon.version-update');

Route::get('/canon/{id}/versions/{version}/diff', function (int $id, int $version) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $diff = $entry->diffFromVersion($version);
    return response()->json($diff);
})->name('api.canon.version-diff');

Route::post('/canon/{id}/versions/{version}/restore', function (int $id, int $version) {
    $entry = \App\Models\CanonEntry::findOrFail($id);
    if ($entry->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $restored = $entry->restoreToVersion($version);
    return response()->json(['restored' => $restored ? true : false]);
})->name('api.canon.version-restore');


Route::post('/embeddings/generate/{type}/{id}', function (string $type, int $id) {
    $gen = app(\App\Services\AI\EmbeddingGenerator::class);
    $emb = $gen->generateFor($type, $id);
    return response()->json(['generated' => $emb ? true : false]);
})->name('api.embeddings.generate');

Route::get('/embeddings/search', function (Illuminate\Http\Request $request) {
    $gen = app(\App\Services\AI\EmbeddingGenerator::class);
    $results = $gen->semanticSearch($request->q, $request->project_id, [
        'types' => $request->types ? explode(',', $request->types) : ['canon'],
        'limit' => $request->limit ?? 10,
    ]);
    return response()->json($results);
})->name('api.embeddings.search');

Route::get('/embeddings/project/{project}/stats', function (int $project) {
    $gen = app(\App\Services\AI\EmbeddingGenerator::class);
    return response()->json($gen->getProjectEmbeddingStats($project));
})->name('api.embeddings.stats');



Route::get('/search/semantic', function (Illuminate\Http\Request $request) {
    $request->validate(['q' => 'required|string', 'project_id' => 'required|integer', 'type' => 'nullable|string', 'limit' => 'nullable|integer']);
    
    $search = app(\App\Services\AI\SemanticSearchService::class);
    $type = $request->type ?? 'all';
    $limit = $request->limit ?? 5;
    
    $results = match($type) {
        'canon' => ['canon' => $search->searchCanon($request->q, $request->project_id, $limit)],
        'references' => ['references' => $search->searchReferences($request->q, $request->project_id, $limit)],
        'sessions' => ['sessions' => $search->searchSessions($request->q, $request->project_id, $limit)],
        default => $search->searchAll($request->q, $request->project_id)['results'],
    };
    
    return response()->json($results);
})->name('api.search.semantic');

Route::get('/search/context', function (Illuminate\Http\Request $request) {
    $request->validate(['q' => 'required|string', 'project_id' => 'required|integer', 'threshold' => 'nullable|numeric']);
    
    $search = app(\App\Services\AI\SemanticSearchService::class);
    $context = $search->getRelevantContext($request->q, $request->project_id, $request->threshold ?? 0.5);
    
    return response()->json($context);
})->name('api.search.context');



# Timeline Events
Route::get('/projects/{project}/timeline', function (int $project) {
    $events = \App\Models\TimelineEvent::forProject($project);
    return response()->json($events->map(fn($e) => $e->getSummary()));
})->name('api.timeline.index');

Route::post('/projects/{project}/timeline', function (Illuminate\Http\Request $request, int $project) {
    $p = \App\Models\Project::findOrFail($project);
    if ($p->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'event_timestamp' => 'nullable|date',
        'order_index' => 'nullable|integer',
    ]);
    
    $maxOrder = \App\Models\TimelineEvent::where('project_id', $project)->max('order_index') ?? 0;
    
    $event = \App\Models\TimelineEvent::create([
        'project_id' => $project,
        'session_id' => $request->session_id,
        'user_id' => auth()->id(),
        'title' => $validated['title'],
        'description' => $validated['description'] ?? null,
        'order_index' => $validated['order_index'] ?? $maxOrder + 1,
        'event_timestamp' => $validated['event_timestamp'] ?? null,
    ]);
    
    return response()->json($event, 201);
})->name('api.timeline.create');

Route::put('/timeline/{id}', function (Illuminate\Http\Request $request, int $id) {
    $event = \App\Models\TimelineEvent::findOrFail($id);
    if ($event->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $event->update($request->only(['title', 'description', 'order_index', 'event_timestamp']));
    return response()->json($event);
})->name('api.timeline.update');

Route::delete('/timeline/{id}', function (int $id) {
    $event = \App\Models\TimelineEvent::findOrFail($id);
    if ($event->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $event->delete();
    return response()->json(['deleted' => true]);
})->name('api.timeline.delete');

Route::post('/timeline/{id}/canon/{canonId}', function (int $id, int $canonId) {
    $event = \App\Models\TimelineEvent::findOrFail($id);
    if ($event->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $event->addCanon($canonId);
    return response()->json(['added' => true]);
})->name('api.timeline.add-canon');

Route::post('/timeline/{id}/reorder', function (Illuminate\Http\Request $request, int $id) {
    $event = \App\Models\TimelineEvent::findOrFail($id);
    if ($event->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $event->reorder($request->order_index);
    return response()->json(['reordered' => true]);
})->name('api.timeline.reorder');



# Timeline ordering
Route::post('/projects/{project}/timeline/validate', function (int $project) {
    $validation = \App\Models\TimelineEvent::validateTimeline($project);
    return response()->json($validation);
})->name('api.timeline.validate');

Route::post('/projects/{project}/timeline/order-by-timestamp', function (int $project) {
    \App\Models\TimelineEvent::orderByTimestamp($project);
    return response()->json(['ordered' => true]);
})->name('api.timeline.order-timestamp');

Route::get('/projects/{project}/timeline/sequences', function (int $project) {
    $sequences = \App\Models\TimelineEvent::detectSequences($project);
    return response()->json(['sequences' => $sequences]);
})->name('api.timeline.sequences');

Route::get('/projects/{project}/timeline/suggest-position', function (Illuminate\Http\Request $request, int $project) {
    $position = \App\Models\TimelineEvent::suggestPosition($project, $request->before_title);
    return response()->json(['suggested_position' => $position]);
})->name('api.timeline.suggest-position');

Route::post('/timeline/{id}/move', function (Illuminate\Http\Request $request, int $id) {
    $event = \App\Models\TimelineEvent::findOrFail($id);
    if ($event->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    \App\Models\TimelineEvent::reorderEvent($id, $request->new_index);
    return response()->json(['moved' => true]);
})->name('api.timeline.move');



Route::post('/continuity/check', function (Illuminate\Http\Request $request) {
    $request->validate(['project_id' => 'required|integer', 'characters' => 'nullable|array', 'timeline_event' => 'nullable|array', 'locations' => 'nullable|array', 'session_id' => 'nullable|integer']);
    $service = app(\App\Services\AI\ContinuityService::class);
    $result = $service->checkContinuity($request->project_id, $request->all());
    return response()->json($result);
})->name('api.continuity.check');

Route::get('/continuity/{project}/summary', function (int $project) {
    $service = app(\App\Services\AI\ContinuityService::class);
    return response()->json($service->getProjectContinuitySummary($project));
})->name('api.continuity.summary');



Route::get('/projects/{project}/timeline/ui', function (int $project) {
    $project = \App\Models\Project::findOrFail($project);
    if ($project->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    // Get timeline events grouped
    $events = \App\Models\TimelineEvent::where('project_id', $project)
        ->orderBy('order_index')
        ->get();
    
    // Group by session
    $bySession = $events->groupBy('session_id')->map(function ($sessionEvents) {
        return $sessionEvents->map(fn($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'order' => $e->order_index,
            'timestamp' => $e->event_timestamp?->toISOString(),
            'canon_count' => count($e->related_canon ?? []),
        ]);
    });
    
    // Timeline with sessions
    $timeline = [
        'project' => ['id' => $project->id, 'name' => $project->name],
        'events' => $events->map(fn($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'order' => $e->order_index,
            'timestamp' => $e->event_timestamp?->toISOString(),
            'session_id' => $e->session_id,
            'session_name' => $e->session?->name,
            'canon' => $e->getCanonEntries(),
        ]),
        'by_session' => $bySession,
        'stats' => [
            'total' => $events->count(),
            'with_sessions' => $events->whereNotNull('session_id')->count(),
            'with_canon' => $events->filter(fn($e) => !empty($e->related_canon))->count(),
        ],
    ];
    
    return response()->json($timeline);
})->name('api.timeline.ui');

Route::get('/projects/{project}/timeline/compact', function (int $project) {
    $events = \App\Models\TimelineEvent::where('project_id', $project)
        ->orderBy('order_index')
        ->get(['id', 'title', 'order_index', 'event_timestamp', 'session_id', 'description']);
    
    return response()->json([
        'events' => $events->map(fn($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'order' => $e->order_index,
            'date' => $e->event_timestamp?->format('Y-m-d'),
        ]),
    ]);
})->name('api.timeline.compact');



Route::get('/conflicts/{project}', function (int $project) {
    $service = app(\App\Services\AI\ConflictDetectionService::class);
    return response()->json($service->detectAllConflicts($project));
})->name('api.conflicts.detect');

Route::get('/conflicts/{project}/summary', function (int $project) {
    $service = app(\App\Services\AI\ConflictDetectionService::class);
    return response()->json($service->getSummary($project));
})->name('api.conflicts.summary');



# Conflict management
Route::get('/projects/{project}/conflicts', function (int $project) {
    return response()->json(\App\Models\Conflict::forProject($project));
})->name('api.conflicts.index');

Route::get('/projects/{project}/conflicts/active', function (int $project) {
    return response()->json(\App\Models\Conflict::active($project));
})->name('api.conflicts.active');

Route::post('/conflicts/{id}/acknowledge', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $conflict->acknowledge();
    return response()->json(['status' => 'acknowledged']);
})->name('api.conflicts.acknowledge');

Route::post('/conflicts/{id}/resolve', function (Illuminate\Http\Request $request, int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $conflict->resolve($request->fix);
    return response()->json(['status' => 'resolved']);
})->name('api.conflicts.resolve');

Route::post('/conflicts/{id}/ignore', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $conflict->ignore();
    return response()->json(['status' => 'ignored']);
})->name('api.conflicts.ignore');

Route::post('/projects/{project}/conflicts/sync', function (int $project) {
    $count = \App\Models\Conflict::syncFromDetection($project);
    return response()->json(['synced' => $count]);
})->name('api.conflicts.sync');



# Conflict resolution
Route::get('/conflicts/{id}/review', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json($conflict->getReviewDetails());
})->name('api.conflicts.review');

Route::post('/conflicts/{id}/accept', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $result = $conflict->acceptSuggestion();
    return response()->json($result);
})->name('api.conflicts.accept');

Route::post('/conflicts/{id}/reject', function (Illuminate\Http\Request $request, int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $request->validate(['reason' => 'required|string']);
    $conflict->rejectSuggestion($request->reason);
    return response()->json(['status' => 'rejected']);
})->name('api.conflicts.reject');

Route::post('/conflicts/{id}/resolve-manual', function (Illuminate\Http\Request $request, int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $conflict->resolveManually($request->all());
    return response()->json(['status' => 'resolved']);
})->name('api.conflicts.resolve-manual');

Route::post('/conflicts/{id}/auto-resolve', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $conflict->resolve('Auto-resolved');
    return response()->json(['status' => 'resolved']);
})->name('api.conflicts.auto-resolve');



Route::get('/conflicts/{id}/suggest', function (int $id) {
    $conflict = \App\Models\Conflict::findOrFail($id);
    if ($conflict->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $assistant = app(\App\Services\AI\ConflictResolutionAssistant::class);
    $suggestions = $assistant->suggestResolution($conflict);
    $explanation = $assistant->explainConflict($conflict);
    return response()->json(array_merge($suggestions, ['explanation' => $explanation]));
})->name('api.conflicts.suggest');



# Output versioning
Route::get('/outputs/{id}/versions', function (int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    return response()->json($output->getVersionHistory()->map(fn($o) => $o->getSummary()));
})->name('api.outputs.versions');

Route::post('/outputs/{id}/versions', function (Illuminate\Http\Request $request, int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $request->validate(['result' => 'required|string', 'reason' => 'nullable|string']);
    $newVersion = $output->createNewVersion($request->result, $request->reason);
    return response()->json(['version' => $newVersion->version, 'id' => $newVersion->id]);
})->name('api.outputs.create-version');

Route::post('/outputs/{id}/versions/{version}/restore', function (int $id, int $version) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $restored = $output->restoreToVersion($version);
    return response()->json(['restored' => $restored ? true : false, 'new_version' => $restored?->version]);
})->name('api.outputs.restore-version');



# Output to session memory
Route::post('/outputs/{id}/to-draft', function (int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $output->saveToSessionDraft();
    return response()->json(['saved' => true]);
})->name('api.outputs.to-draft');

Route::post('/outputs/{id}/to-references', function (int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $output->saveToSessionReferences();
    return response()->json(['saved' => true]);
})->name('api.outputs.to-references');

Route::post('/outputs/{id}/to-canon', function (Illuminate\Http\Request $request, int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $candidate = $output->promoteToCanon($request->all());
    return response()->json(['candidate_id' => $candidate->id]);
})->name('api.outputs.to-canon');



# Output export
Route::get('/outputs/{id}/export', function (Illuminate\Http\Request $request, int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $format = $request->get('format', 'json');
    $exporter = app(\App\Services\AI\OutputExporter::class);
    $export = $exporter->export($output, $format);
    if (!$export['success']) return response()->json($export, 400);
    return response()->json($export);
})->name('api.outputs.export');

Route::get('/outputs/{id}/download', function (Illuminate\Http\Request $request, int $id) {
    $output = \App\Models\AIOutput::findOrFail($id);
    if ($output->session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    $format = $request->get('format', 'json');
    $exporter = app(\App\Services\AI\OutputExporter::class);
    $path = $exporter->exportToFile($output, $format);
    if (!$path) return response()->json(['error' => 'Export failed'], 400);
    return response()->download(storage_path('app/' . $path))->deleteFileAfterSend(false);
})->name('api.outputs.download');



Route::get('/search', function (Illuminate\Http\Request $request) {
    $query = $request->get('q', '');
    if (strlen($query) < 2) return response()->json(['error' => 'Query too short'], 400);
    
    $service = app(\App\Services\GlobalSearchService::class);
    $results = $service->search($query, auth()->id(), [
        'limit' => $request->get('limit', 20),
        'types' => $request->get('types') ? explode(',', $request->types) : null,
    ]);
    
    return response()->json($results);
})->name('api.search');



# Semantic search
Route::get('/search/semantic', function (Illuminate\Http\Request $request) {
    $query = $request->get('q', '');
    if (strlen($query) < 2) return response()->json(['error' => 'Query too short'], 400);
    
    $service = app(\App\Services\SemanticSearchService::class);
    $results = $service->findRelevantContext($query, auth()->id(), [
        'limit' => $request->get('limit', 10),
        'min_score' => $request->get('min_score', 0.5),
        'types' => $request->get('types') ? explode(',', $request->types) : null,
    ]);
    
    return response()->json($results);
})->name('api.search.semantic');

Route::get('/search/context', function (Illuminate\Http\Request $request) {
    $query = $request->get('q', '');
    if (strlen($query) < 2) return response()->json(['error' => 'Query too short'], 400);
    
    $service = app(\App\Services\SemanticSearchService::class);
    $summary = $service->getContextSummary($query, auth()->id(), $request->get('max_items', 5));
    
    return response()->json(['context' => $summary]);
})->name('api.search.context');

Route::post('/search/index/{type}/{id}', function (Illuminate\Http\Request $request, string $type, int $id) {
    $service = app(\App\Services\SemanticSearchService::class);
    try {
        $embedding = $service->indexEntity($type, $id, $request->get('text'));
        return response()->json(['indexed' => true, 'id' => $embedding->id]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
})->name('api.search.index');



# Quick lookups
Route::get('/lookup/project', function (Illuminate\Http\Request $request) {
    $project = app(\App\Services\LookupService::class)->findProject($request->name, auth()->id());
    return $project ? response()->json(['id' => $project->id, 'name' => $project->name]) : response()->json(['error' => 'Not found'], 404);
})->name('api.lookup.project');

Route::get('/lookup/canon', function (Illuminate\Http\Request $request) {
    $canon = app(\App\Services\LookupService::class)->findCanon($request->title, $request->project, auth()->id());
    return $canon ? response()->json(['id' => $canon->id, 'title' => $canon->title, 'type' => $canon->type]) : response()->json(['error' => 'Not found'], 404);
})->name('api.lookup.canon');

Route::get('/lookup/references/by-tag', function (Illuminate\Http\Request $request) {
    $refs = app(\App\Services\LookupService::class)->findReferencesByTag($request->tag, $request->project);
    return response()->json($refs);
})->name('api.lookup.references.tag');

Route::get('/lookup/sessions/by-project', function (Illuminate\Http\Request $request) {
    $sessions = app(\App\Services\LookupService::class)->findSessionsByProject($request->project, $request->status);
    return response()->json($sessions);
})->name('api.lookup.sessions.project');

Route::get('/lookup/quick', function (Illuminate\Http\Request $request) {
    $results = app(\App\Services\LookupService::class)->quickLookup($request->q, auth()->id());
    return response()->json($results);
})->name('api.lookup.quick');



# Job status tracking
Route::get('/jobs/status', function (Illuminate\Http\Request $request) {
    $type = $request->get('type', 'output');
    $status = $request->get('status');
    
    $query = match($type) {
        'output' => \App\Models\AIOutput::query(),
        'reference' => \App\Models\ReferenceImage::query(),
        default => null,
    };
    
    if (!$query) return response()->json(['error' => 'Invalid type'], 400);
    
    if ($status) {
        $query->where('status', $status);
    }
    
    $jobs = $query->orderBy('created_at', 'desc')
        ->limit($request->get('limit', 20))
        ->get()
        ->map(fn($job) => [
            'id' => $job->id,
            'type' => $type,
            'status' => $job->status ?? $job->getMetadata('processing_status'),
            'error' => $job->error_message ?? $job->getMetadata('processing_error'),
            'created_at' => $job->created_at->toISOString(),
        ]);
    
    return response()->json($jobs);
})->name('api.jobs.status');

Route::get('/jobs/{type}/{id}/status', function (string $type, int $id) {
    $service = app(\App\Services\JobRetryService::class);
    return response()->json($service->getJobStatus($type, $id));
})->name('api.jobs.check');

Route::post('/jobs/{type}/{id}/retry', function (string $type, int $id) {
    $service = app(\App\Services\JobRetryService::class);
    $success = $service->retryJob($type, $id);
    return response()->json(['retry_scheduled' => $success]);
})->name('api.jobs.retry');

Route::get('/projects/{id}/jobs-summary', function (int $id) {
    $outputs = \App\Models\AIOutput::whereHas('session', fn($q) => $q->where('project_id', $id))->get();
    
    $summary = [
        'pending' => $outputs->where('status', 'pending')->count(),
        'processing' => $outputs->where('status', 'processing')->count(),
        'completed' => $outputs->where('status', 'completed')->count(),
        'failed' => $outputs->where('status', 'failed')->count(),
    ];
    
    return response()->json($summary);
})->name('api.jobs.project-summary');



# Activity log
Route::get('/activity', function (Illuminate\Http\Request $request) {
    $limit = $request->get('limit', 20);
    $activities = \App\Models\ActivityLog::recent(auth()->id(), $limit);
    return response()->json($activities->map(fn($a) => $a->getSummary()));
})->name('api.activity.recent');

Route::get('/projects/{id}/activity', function (int $id) {
    $activities = \App\Models\ActivityLog::forProject($id);
    return response()->json($activities->map(fn($a) => $a->getSummary()));
})->name('api.activity.project');



# Activity history views
Route::get('/projects/{id}/history', function (Illuminate\Http\Request $request, int $id) {
    $limit = $request->get('limit', 50);
    $type = $request->get('type'); // filter by event_type
    
    $query = \App\Models\ActivityLog::where('project_id', $id)
        ->orderBy('created_at', 'desc');
    
    if ($type) {
        $query->where('event_type', $type);
    }
    
    $activities = $query->limit($limit)->get()
        ->map(fn($a) => [
            'id' => $a->id,
            'who' => $a->user_id,
            'when' => $a->created_at->toISOString(),
            'event' => $a->event_type,
            'description' => $a->description,
            'entity' => $a->entity_type ? ['type' => $a->entity_type, 'id' => $a->entity_id] : null,
        ]);
    
    return response()->json([
        'project_id' => $id,
        'activities' => $activities,
        'count' => $activities->count(),
    ]);
})->name('api.projects.history');

Route::get('/sessions/{id}/history', function (Illuminate\Http\Request $request, int $id) {
    $session = \App\Models\Session::findOrFail($id);
    if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
    
    $limit = $request->get('limit', 50);
    
    $activities = \App\Models\ActivityLog::where('entity_type', 'session')
        ->where('entity_id', $id)
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->map(fn($a) => [
            'id' => $a->id,
            'when' => $a->created_at->toISOString(),
            'event' => $a->event_type,
            'description' => $a->description,
        ]);
    
    // Also get related outputs
    $outputs = \App\Models\AIOutput::where('session_id', $id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(fn($o) => [
            'type' => 'output',
            'when' => $o->created_at->toISOString(),
            'event' => 'output.generated',
            'description' => "Generated {->type}",
            'status' => $o->status,
        ]);
    
    return response()->json([
        'session_id' => $id,
        'activities' => $activities,
        'recent_outputs' => $outputs,
    ]);
})->name('api.sessions.history');

Route::get('/projects/{id}/history/summary', function (int $id) {
    $logs = \App\Models\ActivityLog::where('project_id', $id)->get();
    
    $byType = $logs->groupBy('event_type')
        ->map(fn($g) => $g->count());
    
    return response()->json([
        'total' => $logs->count(),
        'by_type' => $byType,
        'first_activity' => $logs->min('created_at'),
        'last_activity' => $logs->max('created_at'),
    ]);
})->name('api.projects.history-summary');



# Change history
Route::get('/history/{type}/{id}', function (string $type, int $id) {
    $history = \App\Models\ChangeHistory::forEntity($type, $id);
    return response()->json($history->map(fn($h) => $h->getDiff()));
})->name('api.history.entity');

Route::get('/history/{type}/{id}/{field}', function (string $type, int $id, string $field) {
    $history = \App\Models\ChangeHistory::fieldHistory($type, $id, $field);
    return response()->json($history->map(fn($h) => $h->getDiff()));
})->name('api.history.field');



# Settings
Route::get('/settings', function () {
    return response()->json(\App\Models\Setting::all());
})->name('api.settings.all');

Route::get('/settings/{key}', function (string $key) {
    $value = \App\Models\Setting::get($key);
    return $value !== null ? response()->json(['key' => $key, 'value' => $value]) : response()->json(['error' => 'Not found'], 404);
})->name('api.settings.get');

Route::post('/settings/{key}', function (Illuminate\Http\Request $request, string $key) {
    $request->validate(['value' => 'required', 'type' => 'nullable|string']);
    \App\Models\Setting::set($key, $request->value, $request->type ?? 'string');
    return response()->json(['success' => true]);
})->name('api.settings.set');

Route::delete('/settings/{key}', function (string $key) {
    \App\Models\Setting::forget($key);
    return response()->json(['success' => true]);
})->name('api.settings.delete');

Route::post('/settings/init', function () {
    \App\Models\Setting::initDefaults();
    return response()->json(['initialized' => true]);
})->name('api.settings.init');

Route::get('/settings/ai/config', function () {
    return response()->json(\App\Models\Setting::getAIConfig());
})->name('api.settings.ai');

Route::get('/settings/generation', function () {
    return response()->json(\App\Models\Setting::getGenerationDefaults());
})->name('api.settings.generation');



# Security / Local mode
Route::get('/settings/security', function () {
    return response()->json(\App\Models\Setting::getSecurityConfig());
})->name('api.settings.security');

Route::post('/settings/enforce-local', function () {
    \App\Models\Setting::enforceLocalMode();
    return response()->json(['local_mode_enforced' => true]);
})->name('api.settings.enforce-local');

Route::get('/settings/status', function () {
    return response()->json([
        'name' => \App\Models\Setting::get('app.name', 'DWAI Studio'),
        'local_mode' => \App\Models\Setting::isLocalMode(),
        'version' => '1.0.0',
        'private' => true,
    ]);
})->name('api.settings.status');



# AI Provider management
Route::get('/settings/providers', function () {
    return response()->json(\App\Models\Setting::getProviderStatus());
})->name('api.settings.providers');

Route::post('/settings/providers/{type}', function (Illuminate\Http\Request $request, string $type) {
    $request->validate(['provider' => 'required|string', 'config' => 'nullable|array']);
    \App\Models\Setting::configureAIProvider($type, $request->provider, $request->config ?? []);
    return response()->json(['success' => true, 'provider' => $request->provider]);
})->name('api.settings.set-provider');

Route::post('/settings/providers/{type}/key', function (Illuminate\Http\Request $request, string $type) {
    $request->validate(['key' => 'required|string']);
    $provider = \App\Models\Setting::getAIProvider($type);
    \App\Models\Setting::setProviderAPIKey($provider, $request->key);
    return response()->json(['success' => true]);
})->name('api.settings.set-key');

Route::post('/settings/providers/use-mock', function () {
    \App\Models\Setting::useMockProviders();
    return response()->json(['success' => true]);
})->name('api.settings.use-mock');



# Behavior settings
Route::get('/settings/behavior', function () {
    return response()->json(\App\Models\Setting::getBehaviorConfig());
})->name('api.settings.behavior');

Route::post('/settings/behavior/default-project', function (Illuminate\Http\Request $request) {
    \App\Models\Setting::setDefaultProject($request->project_id);
    return response()->json(['success' => true]);
})->name('api.settings.set-default-project');

Route::post('/settings/behavior/auto-save', function (Illuminate\Http\Request $request) {
    if ($request->has('enabled')) \App\Models\Setting::set('behavior.auto_save_enabled', $request->enabled, 'boolean');
    if ($request->has('interval')) \App\Models\Setting::set('behavior.auto_save_interval_seconds', $request->interval, 'integer');
    return response()->json(\App\Models\Setting::getAutoSaveConfig());
})->name('api.settings.auto-save');

Route::post('/settings/behavior/conflict-strictness', function (Illuminate\Http\Request $request) {
    $request->validate(['level' => 'required|in:error,warning,info,off']);
    \App\Models\Setting::set('behavior.conflict_strictness', $request->level);
    return response()->json(['strictness' => $request->level]);
})->name('api.settings.conflict-strictness');

Route::post('/settings/behavior/promotion', function (Illuminate\Http\Request $request) {
    if ($request->has('requires_review')) \App\Models\Setting::set('behavior.promotion_requires_review', $request->requires_review, 'boolean');
    if ($request->has('auto_promote')) \App\Models\Setting::set('behavior.auto_promote_patterns', $request->auto_promote, 'boolean');
    return response()->json(\App\Models\Setting::getPromotionConfig());
})->name('api.settings.promotion');



# Backup
Route::post('/backup', function () {
    $service = app(\App\Services\BackupService::class);
    $backup = $service->createBackup(auth()->id());
    return response()->json($backup);
})->name('api.backup.create');

Route::get('/backup/list', function () {
    $service = app(\App\Services\BackupService::class);
    return response()->json($service->listBackups());
})->name('api.backup.list');

Route::post('/backup/restore', function (Illuminate\Http\Request $request) (Illuminate\Http\Request $request) {
    $service = app(\App\Services\BackupService::class);
    $success = $service->restoreBackup($request->filename);
    return response()->json(['restored' => $success]);
})->name('api.backup.restore');



Route::post('/backup/preview', function (Illuminate\Http\Request $request) {
    $service = app(\App\Services\BackupService::class);
    $preview = $service->previewRestore($request->filename);
    return response()->json($preview);
})->name('api.backup.preview');



# Project/Session export
Route::post('/projects/{id}/export', function (int $id) {
    $service = app(\App\Services\ExportService::class);
    $export = $service->exportProject($id);
    return response()->json($export);
})->name('api.projects.export');

Route::post('/sessions/{id}/export', function (int $id) {
    $service = app(\App\Services\ExportService::class);
    $export = $service->exportSession($id);
    return response()->json($export);
})->name('api.sessions.export');

Route::get('/exports', function () {
    $service = app(\App\Services\ExportService::class);
    return response()->json($service->listExports());
})->name('api.exports.list');



# Scheduled backup
Route::get('/settings/backup-schedule', function () {
    return response()->json(app(\App\Services\ScheduledBackupService::class)->getSchedule());
})->name('api.settings.backup-schedule');

Route::post('/settings/backup-schedule', function (Illuminate\Http\Request $request) {
    app(\App\Services\ScheduledBackupService::class)->configure($request->all());
    return response()->json(['success' => true]);
})->name('api.settings.configure-backup');

Route::post('/backup/trigger', function () {
    $result = app(\App\Services\ScheduledBackupService::class)->triggerManual(auth()->id());
    return response()->json($result);
})->name('api.backup.trigger');

Route::get('/backup/cron', function () {
    return response()->json(['cron' => app(\App\Services\ScheduledBackupService::class)->getCronExpression()]);
})->name('api.backup.cron');



# Import
Route::post('/projects/{id}/import/canon', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['file' => 'required|file']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importAsCanon($id, $request->file('file'), $request->except('file'));
    return response()->json($result);
})->name('api.projects.import-canon');

Route::post('/projects/{id}/import/reference', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,gif,webp']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importAsReference($id, $request->file('file'), $request->except('file'));
    return response()->json($result);
})->name('api.projects.import-reference');

Route::post('/projects/{id}/import/auto', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['file' => 'required|file']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importAuto($id, $request->file('file'), $request->except('file'));
    return response()->json($result);
})->name('api.projects.import-auto');

Route::post('/projects/{id}/import/batch', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['files' => 'required|array']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->batchImport($id, $request->file('files'), $request->get('mode', 'auto'));
    return response()->json($result);
})->name('api.projects.import-batch');



Route::post('/projects/{id}/import/references', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['files' => 'required|array']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->bulkImportReferences($id, $request->file('files'), $request->except('files'));
    return response()->json($result);
})->name('api.projects.import-references');

Route::post('/projects/{id}/import/style-references', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['files' => 'required|array']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->bulkImportReferences($id, $request->file('files'), [
        'is_style_reference' => true,
        'tags' => ['style', 'imported'],
    ]);
    return response()->json($result);
})->name('api.projects.import-style-references');



# Project package import
Route::post('/projects/import', function (Illuminate\Http\Request $request) {
    $request->validate(['file' => 'required|file|mimes:json']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importProjectPackage($request->file('file'), $request->except('file'));
    return response()->json($result);
})->name('api.projects.import');

Route::post('/sessions/import', function (Illuminate\Http\Request $request, int $projectId) {
    $request->validate(['file' => 'required|file|mimes:json']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importSessionPackage($projectId, $request->file('file'), $request->except('file'));
    return response()->json($result);
})->name('api.sessions.import');



# Session import
Route::post('/sessions/{id}/import/notes', function (Illuminate\Http\Request $request, int $id) {
    $service = app(\App\Services\ImportService::class);
    $result = $service->importToSession($id, $request->content, $request->get('mode', 'append'));
    return response()->json($result);
})->name('api.sessions.import-notes');

Route::post('/sessions/{id}/import/draft', function (Illuminate\Http\Request $request, int $id) {
    $service = app(\App\Services\ImportService::class);
    $result = $service->importDraftToSession($id, $request->content, $request->get('mode', 'append'));
    return response()->json($result);
})->name('api.sessions.import-draft');

Route::post('/sessions/{id}/import/file', function (Illuminate\Http\Request $request, int $id) {
    $request->validate(['file' => 'required|file']);
    $service = app(\App\Services\ImportService::class);
    $result = $service->importFileToSession($id, $request->file('file'), $request->get('target', 'notes'), $request->get('mode', 'append'));
    return response()->json($result);
})->name('api.sessions.import-file');



# DWAI Controllers
Route::get('/dwai/dashboard', [App\Http\Controllers\DWAI\DashboardController::class, 'index']);
Route::get('/dwai/stats', [App\Http\Controllers\DWAI\DashboardController::class, 'stats']);

Route::resource('dwai/projects', App\Http\Controllers\DWAI\ProjectController::class)->except(['create', 'edit']);
Route::resource('dwai/sessions', App\Http\Controllers\DWAI\SessionController::class)->except(['create', 'edit']);
Route::resource('dwai/canon', App\Http\Controllers\DWAI\CanonController::class)->except(['create', 'edit']);
Route::resource('dwai/references', App\Http\Controllers\DWAI\ReferenceController::class)->except(['create', 'edit']);

Route::post('/dwai/ai/generate-text/{session}', [App\Http\Controllers\DWAI\AIController::class, 'generateText']);
Route::post('/dwai/ai/generate-image/{session}', [App\Http\Controllers\DWAI\AIController::class, 'generateImage']);
Route::get('/dwai/ai/outputs/{session}', [App\Http\Controllers\DWAI\AIController::class, 'outputs']);

Route::get('/dwai/conflicts/{project}', [App\Http\Controllers\DWAI\ConflictController::class, 'index']);
Route::post('/dwai/conflicts/{id}/resolve', [App\Http\Controllers\DWAI\ConflictController::class, 'resolve']);
Route::post('/dwai/conflicts/{id}/ignore', [App\Http\Controllers\DWAI\ConflictController::class, 'ignore']);
Route::post('/dwai/conflicts/{project}/scan', [App\Http\Controllers\DWAI\ConflictController::class, 'scan']);

Route::get('/dwai/settings', [App\Http\Controllers\DWAI\SettingsController::class, 'index']);
Route::get('/dwai/settings/{key}', [App\Http\Controllers\DWAI\SettingsController::class, 'get']);
Route::post('/dwai/settings/{key}', [App\Http\Controllers\DWAI\SettingsController::class, 'set']);
Route::delete('/dwai/settings/{key}', [App\Http\Controllers\DWAI\SettingsController::class, 'delete']);
Route::get('/dwai/settings/ai/config', [App\Http\Controllers\DWAI\SettingsController::class, 'aiConfig']);
Route::get('/dwai/settings/behavior', [App\Http\Controllers\DWAI\SettingsController::class, 'behavior']);

Route::prefix('dwai/import-export')->group(function () {
    Route::post('/backup', [App\Http\Controllers\DWAI\ImportExportController::class, 'createBackup']);
    Route::get('/backups', [App\Http\Controllers\DWAI\ImportExportController::class, 'listBackups']);
    Route::post('/restore', [App\Http\Controllers\DWAI\ImportExportController::class, 'restoreBackup']);
    Route::post('/export/project/{id}', [App\Http\Controllers\DWAI\ImportExportController::class, 'exportProject']);
    Route::post('/export/session/{id}', [App\Http\Controllers\DWAI\ImportExportController::class, 'exportSession']);
    Route::post('/import/project', [App\Http\Controllers\DWAI\ImportExportController::class, 'importProject']);
    Route::post('/import/session/{id}', [App\Http\Controllers\DWAI\ImportExportController::class, 'importToSession']);
    Route::get('/schedule', [App\Http\Controllers\DWAI\ImportExportController::class, 'getSchedule']);
    Route::post('/schedule', [App\Http\Controllers\DWAI\ImportExportController::class, 'configureSchedule']);
    Route::post('/backup/trigger', [App\Http\Controllers\DWAI\ImportExportController::class, 'triggerBackup']);
});



# Unified data endpoints
Route::get('/dwai/unified/project/{id}', function (int $id) {
    $service = app(\App\Services\DWAI\UnifiedDataService::class);
    return response()->json($service->getProjectData($id));
})->name('api.unified.project');

Route::get('/dwai/unified/session/{id}', function (int $id) {
    $service = app(\App\Services\DWAI\UnifiedDataService::class);
    return response()->json($service->getSessionData($id));
})->name('api.unified.session');

Route::get('/dwai/unified/ai-context/{sessionId}', function (int $sessionId) {
    $service = app(\App\Services\DWAI\UnifiedDataService::class);
    return response()->json($service->getAIContext($sessionId));
})->name('api.unified.ai-context');

Route::get('/dwai/unified/dashboard', function () {
    $service = app(\App\Services\DWAI\UnifiedDataService::class);
    return response()->json($service->getDashboardData(auth()->id()));
})->name('api.unified.dashboard');

Route::get('/dwai/unified/search', function (Illuminate\Http\Request $request) {
    $service = app(\App\Services\DWAI\UnifiedSearchService::class);
    return response()->json($service->search($request->q, auth()->id(), $request->all()));
})->name('api.unified.search');



# Assistant Controller Routes
Route::post('/dwai/assistant/{sessionId}/handle', [App\Http\Controllers\DWAI\AssistantController::class, 'handle']);
Route::get('/dwai/assistant/{sessionId}/state', [App\Http\Controllers\DWAI\AssistantController::class, 'state']);
Route::post('/dwai/assistant/{sessionId}/reset', [App\Http\Controllers\DWAI\AssistantController::class, 'reset']);



# Progressive Session Routes
Route::post('/dwai/progressive/{sessionId}/handle', [App\Http\Controllers\DWAI\ProgressiveSessionController::class, 'handle']);
Route::get('/dwai/progressive/{sessionId}/state', [App\Http\Controllers\DWAI\ProgressiveSessionController::class, 'state']);
Route::post('/dwai/progressive/{sessionId}/next', [App\Http\Controllers\DWAI\ProgressiveSessionController::class, 'next']);
Route::post('/dwai/progressive/{sessionId}/refine', [App\Http\Controllers\DWAI\ProgressiveSessionController::class, 'refine']);

