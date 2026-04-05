<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use App\Models\Session;
use App\Models\AIOutput;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SessionController extends WebController
{
    /**
     * Display all sessions across projects.
     */
    public function index(Request $request): View
    {
        $query = Session::whereHas('project', function ($q) {
            $q->where('user_id', auth()->id());
        })->with('project');

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $sessions = $query->orderBy('updated_at', 'desc')->get();

        return $this->view('sessions.index', compact('sessions'));
    }

    /**
     * Show create form.
     */
    public function create(Request $request): View
    {
        // Get user's projects for selection
        $projects = auth()->user()->projects()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $preSelectedProject = $request->get('project');

        return $this->view('sessions.create', compact('projects', 'preSelectedProject'));
    }

    /**
     * Store new session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:brainstorm,script,storyboard,edit',
        ]);

        // Verify project ownership
        $project = Project::findOrFail($validated['project_id']);
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $session = auth()->user()->sessions()->create($validated);

        return redirect()->route('sessions.show', $session->id)
            ->with('success', 'Session created!');
    }

    /**
     * Display single session.
     */
    public function show(int $id): View
    {
        $session = Session::with([
            'project',
            'aiOutputs' => fn($q) => $q->orderBy('created_at', 'desc'),
            'timelineEvents' => fn($q) => $q->orderBy('order_index'),
        ])->findOrFail($id);

        $this->authorizeSession($session);

        $project = $session->project;
        $outputs = $session->aiOutputs;
        $timeline = $session->timelineEvents;
        $memory = $session->getShortTermMemory();

        return $this->view('sessions.show', compact('session', 'project', 'outputs', 'timeline', 'memory'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $types = ['brainstorm', 'script', 'storyboard', 'edit'];
        $statuses = ['active', 'completed', 'archived'];

        return $this->view('sessions.edit', compact('session', 'types', 'statuses'));
    }

    /**
     * Update session.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:brainstorm,script,storyboard,edit',
            'status' => 'nullable|string|in:active,completed,archived',
            'notes' => 'nullable|string',
        ]);

        $session->update($validated);

        return redirect()->route('sessions.show', $session->id)
            ->with('success', 'Session updated!');
    }

    /**
     * Close session (mark as completed).
     */
    public function close(int $id): RedirectResponse
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $session->update([
            'status' => 'completed',
        ]);

        return redirect()->route('sessions.show', $session->id)
            ->with('success', 'Session closed.');
    }

    /**
     * Archive session.
     */
    public function archive(int $id): RedirectResponse
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $session->update(['status' => 'archived']);

        return redirect()->route('sessions.index')
            ->with('success', 'Session archived.');
    }

    /**
     * Resume archived session.
     */
    public function resume(int $id): RedirectResponse
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $session->update(['status' => 'active']);

        return redirect()->route('sessions.show', $session->id)
            ->with('success', 'Session resumed!');
    }

    /**
     * Delete session (with confirmation).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $session = Session::findOrFail($id);
        $this->authorizeSession($session);

        $request->validate([
            'confirm_delete' => 'required|accepted',
        ]);

        $session->delete();

        return redirect()->route('sessions.index')
            ->with('success', 'Session deleted.');
    }

    /**
     * Authorize session access.
     */
    protected function authorizeSession(Session $session): void
    {
        if ($session->project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this session.');
        }
    }
}
