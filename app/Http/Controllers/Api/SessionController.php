<?php

namespace App\Http\Controllers\Api;

use App\Models\Session;
use Illuminate\Http\JsonResponse;

class SessionController extends ApiController
{
    /**
     * Get session summary for production tracking.
     */
    public function summary(int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        // Check ownership
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        return $this->success($session->getFullSummary());
    }

    /**
     * Get session short-term memory.
     */
    public function memory(int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        return $this->success($session->getShortTermMemory());
    }

    /**
     * Get recent AI outputs.
     */
    public function outputs(int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        return $this->success([
            'outputs' => $session->getRecentOutputs(10),
            'count' => $session->output_count,
        ]);
    }

    /**
     * Get current errors.
     */
    public function errors(int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        return $this->success([
            'errors' => $session->getCurrentErrors(),
            'count' => count($session->getCurrentErrors()),
        ]);
    }

    /**
     * Update short-term memory.
     */
    public function updateMemory(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        if ($request->has('temp_notes')) {
            $session->updateTempNotes($request->temp_notes);
        }
        
        if ($request->has('ai_reasoning')) {
            $session->updateAIReasoning($request->ai_reasoning);
        }
        
        if ($request->has('draft_text')) {
            $session->updateDraftText($request->draft_text);
        }
        
        return $this->success($session->getShortTermMemory(), 'Memory updated');
    }

    /**
     * Add session reference.
     */
    public function addReference(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        $validated = $request->validate([
            'id' => 'required|string',
            'url' => 'required|url',
            'title' => 'nullable|string',
        ]);
        
        $session->addSessionReference($validated);
        
        return $this->success($session->getCurrentReferences(), 'Reference added');
    }

    /**
     * Clear short-term memory.
     */
    public function clearMemory(int $id): JsonResponse
    {
        $session = Session::with('project')->findOrFail($id);
        
        if ($session->project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }
        
        $session->clearShortTermMemory();
        
        return $this->success(null, 'Memory cleared');
    }
}
