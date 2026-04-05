<?php

namespace App\Http\Controllers\Api;

use App\Models\CanonCandidate;
use App\Models\CanonEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CanonCandidateController extends ApiController
{
    /**
     * List candidates for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $query = CanonCandidate::forProject($projectId);
        
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $candidates = $query->with('session:id,name')->orderBy('created_at', 'desc')->paginate(20);
        
        return $this->success($candidates);
    }

    /**
     * Create a candidate from session or output.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'session_id' => 'nullable|exists:sessions,id',
            'source_output_id' => 'nullable|exists:ai_outputs,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:character,location,lore,rule,timeline_event,note',
            'content' => 'nullable|string',
            'ai_reasoning' => 'nullable|string',
            'tags' => 'nullable|array',
            'importance' => 'nullable|in:none,minor,important,critical',
        ]);

        $project = \App\Models\Project::findOrFail($validated['project_id']);
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $candidate = CanonCandidate::create([
            'user_id' => auth()->id(),
            'project_id' => $validated['project_id'],
            'session_id' => $validated['session_id'] ?? null,
            'source_output_id' => $validated['source_output_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'ai_reasoning' => $validated['ai_reasoning'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'importance' => $validated['importance'] ?? 'none',
        ]);

        return $this->success($candidate, 'Candidate created', 201);
    }

    /**
     * Show candidate with context.
     */
    public function show(int $id): JsonResponse
    {
        $candidate = CanonCandidate::with('session', 'sourceOutput')->findOrFail($id);
        
        if ($candidate->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $candidate->context = $candidate->getContext();
        
        return $this->success($candidate);
    }

    /**
     * Approve candidate - promote to canon.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $candidate = CanonCandidate::findOrFail($id);
        
        if ($candidate->user_id !== auth()->id() && $candidate->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $notes = $request->get('notes');
        
        $canon = $candidate->approve($notes);
        
        return $this->success([
            'canon_entry' => $canon,
            'candidate' => $candidate,
        ], 'Promoted to canon');
    }

    /**
     * Edit and approve.
     */
    public function editAndApprove(Request $request, int $id): JsonResponse
    {
        $candidate = CanonCandidate::findOrFail($id);
        
        if ($candidate->user_id !== auth()->id() && $candidate->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:character,location,lore,rule,timeline_event,note',
            'content' => 'nullable|string',
            'tags' => 'nullable|array',
            'importance' => 'nullable|in:none,minor,important,critical',
            'notes' => 'nullable|string',
        ]);

        $canon = $candidate->approveWithEdit($validated, $validated['notes'] ?? null);
        
        return $this->success([
            'canon_entry' => $canon,
            'candidate' => $candidate,
        ], 'Edited and promoted to canon');
    }

    /**
     * Reject candidate.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $candidate = CanonCandidate::findOrFail($id);
        
        if ($candidate->user_id !== auth()->id() && $candidate->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate(['reason' => 'required|string']);
        
        $candidate->reject($request->reason);
        
        return $this->success($candidate, 'Candidate rejected');
    }

    /**
     * Update candidate (edit before review).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $candidate = CanonCandidate::findOrFail($id);
        
        if ($candidate->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        if ($candidate->status !== 'pending') {
            return $this->error('Can only edit pending candidates', 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:character,location,lore,rule,timeline_event,note',
            'content' => 'nullable|string',
            'tags' => 'nullable|array',
            'importance' => 'nullable|in:none,minor,important,critical',
        ]);

        $candidate->update($validated);
        
        return $this->success($candidate);
    }

    /**
     * Quick create from session draft.
     */
    public function createFromSession(Request $request, int $sessionId): JsonResponse
    {
        $session = \App\Models\Session::with('project')->findOrFail($sessionId);
        
        if ($session->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:character,location,lore,rule,timeline_event,note',
            'tags' => 'nullable|array',
            'importance' => 'nullable|in:none,minor,important,critical',
        ]);

        $candidate = CanonCandidate::createFromSession($session, $validated);
        
        return $this->success($candidate, 'Candidate created from session', 201);
    }

    /**
     * Stats for dashboard.
     */
    public function stats(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $stats = [
            'pending' => CanonCandidate::forProject($projectId)->pending()->count(),
            'approved' => CanonCandidate::forProject($projectId)->approved()->count(),
            'rejected' => CanonCandidate::forProject($projectId)->rejected()->count(),
            'total' => CanonCandidate::forProject($projectId)->count(),
        ];

        return $this->success($stats);
    }
}
