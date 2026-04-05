<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends WebController
{
    /**
     * Display all projects (excludes archived by default).
     */
    public function index(Request $request): View
    {
        $query = auth()->user()->projects()->withCount(['sessions', 'canonEntries', 'referenceImages']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Show archived?
        if ($request->get('include_archived', false)) {
            $projects = $query->orderBy('updated_at', 'desc')->get();
        } else {
            $projects = $query->where('is_archived', false)->orderBy('updated_at', 'desc')->get();
        }

        return $this->view('projects.index', compact('projects'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $types = ['Film', 'Comic', 'Music Video', 'TV Series', 'Short Film', 'Web Series'];
        return $this->view('projects.create', compact('types'));
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

        // Add tags if provided
        if ($request->has('tags')) {
            $project->update(['tags' => $request->tags]);
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project created!');
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

        $this->authorizeProject($project);

        return $this->view('projects.show', compact('project'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        $types = ['Film', 'Comic', 'Music Video', 'TV Series', 'Short Film', 'Web Series'];
        $statuses = ['draft', 'active', 'completed', 'archived'];

        return $this->view('projects.edit', compact('project', 'types', 'statuses'));
    }

    /**
     * Update project.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'status' => 'nullable|string|in:draft,active,completed,archived',
            'progress' => 'nullable|integer|min:0|max:100',
            'visual_style_description' => 'nullable|string',
            'tags' => 'nullable|array',
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
        $this->authorizeProject($project);

        $project->archive();

        return redirect()->route('projects.index')
            ->with('success', 'Project archived.');
    }

    /**
     * Unarchive project.
     */
    public function unarchive(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        $project->unarchive();

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project restored.');
    }

    /**
     * Delete project (admin only).
     */
    public function destroy(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        // Admin or owner only
        if ($project->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    /**
     * Helper: Authorize project access.
     */
    protected function authorizeProject(Project $project): void
    {
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }
    }
}
