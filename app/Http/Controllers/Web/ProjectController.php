<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends WebController
{
    /**
     * Display all projects.
     */
    public function index(Request $request): View
    {
        $query = auth()->user()->projects()
            ->withCount(['sessions', 'canonEntries', 'referenceImages']);

        // Filter options
        if ($request->status === 'archived') {
            $query->where('is_archived', true);
        } elseif ($request->status !== 'all') {
            $query->where('is_archived', false);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $projects = $query->orderBy('updated_at', 'desc')->get();

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
        $statuses = ['draft', 'active', 'completed'];

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
            'status' => 'nullable|string|in:draft,active,completed',
            'progress' => 'nullable|integer|min:0|max:100',
            'visual_style_description' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project updated!');
    }

    /**
     * Archive project (soft archive, reversible).
     */
    public function archive(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        if ($project->is_archived) {
            return back()->with('info', 'Project is already archived.');
        }

        $project->archive();

        return redirect()->route('projects.index')
            ->with('success', 'Project archived. You can restore it later.');
    }

    /**
     * Unarchive project.
     */
    public function unarchive(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        if (!$project->is_archived) {
            return back()->with('info', 'Project is not archived.');
        }

        $project->unarchive();

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project restored!');
    }

    /**
     * Show delete confirmation.
     */
    public function confirmDelete(int $id): View
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        // Get counts for warning
        $counts = $project->getCounts();

        return $this->view('projects.confirm-delete', compact('project', 'counts'));
    }

    /**
     * Delete project (with confirmation).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $this->authorizeProject($project);

        // Require explicit confirmation
        $request->validate([
            'confirm_delete' => 'required|accepted',
            'project_name' => 'required|string|in:' . $project->name,
        ]);

        $projectName = $project->name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project '{$projectName}' permanently deleted.");
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
