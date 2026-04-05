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



# Add relationships for embeddings count
# In CanonEntry model, add:
public function embeddings() {
    return $this->morphMany(\App\Models\Embedding::class, 'entity', 'entity_type', 'entity_id');
}

# Add to routes:
Route::post('/embeddings/generate/project/{project}', function (int $project) {
    $gen = app(\App\Services\AI\EmbeddingGenerator::class);
    $result = $gen->generateForProject($project);
    return response()->json($result);
})->name('api.embeddings.generate-project');

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

