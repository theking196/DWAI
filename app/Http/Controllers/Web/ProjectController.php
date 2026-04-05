<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\TimelineEvent;
use App\Models\Conflict;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends WebController
{
    /**
     * Display all projects.
     */
    public function index(): View
    {
        $projects = auth()->user()
            ->projects()
            ->withCount(['sessions', 'canonEntries', 'referenceImages'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->view('projects.index', compact('projects'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return $this->view('projects.create');
    }

    /**
     * Store new project.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
        ]);

        $project = auth()->user()->projects()->create($validated);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display single project.
     */
    public function show(int $id): View
    {
        $project = Project::with([
            'sessions' => fn($q) => $q->orderBy('created_at', 'desc'),
            'canonEntries' => fn($q) => $q->orderBy('created_at', 'desc'),
            'referenceImages' => fn($q) => $q->orderBy('created_at', 'desc'),
            'timelineEvents' => fn($q) => $q->orderBy('order_index'),
            'conflicts' => fn($q) => $q->unresolved(),
        ])->findOrFail($id);

        // Check ownership
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }

        $sessions = $project->sessions;
        $canon = $project->canonEntries;
        $references = $project->referenceImages;
        $timeline = $project->timelineEvents;
        $conflicts = $project->conflicts;

        return $this->view('projects.show', compact(
            'project', 'sessions', 'canon', 'references', 'timeline', 'conflicts'
        ));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $project = Project::findOrFail($id);

        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->view('projects.edit', compact('project'));
    }

    /**
     * Update project.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'status' => 'nullable|string|in:draft,active,completed,archived',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project updated!');
    }

    /**
     * Archive project.
     */
    public function archive(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->update(['status' => 'archived']);

        return redirect()->route('projects.index')
            ->with('success', 'Project archived.');
    }

    /**
     * Delete project (admin only).
     */
    public function destroy(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        // Only admin or owner can delete
        if ($project->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }
}
