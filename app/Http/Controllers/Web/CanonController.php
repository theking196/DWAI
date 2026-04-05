<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use App\Models\CanonEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CanonController extends WebController
{
    /**
     * Display canon entries for a project.
     */
    public function index(Request $request, int $projectId): View
    {
        $project = Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $query = $project->canonEntries();

        // Filter by type
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by importance
        if ($request->importance && $request->importance !== 'all') {
            $query->where('importance', $request->importance);
        }

        // Filter by tag
        if ($request->tag) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $entries = $query->orderBy('created_at', 'desc')->get();
        $types = ['character', 'location', 'lore', 'rule', 'timeline_event', 'note'];

        return $this->view('canon.index', compact('project', 'entries', 'types'));
    }

    /**
     * Show create form.
     */
    public function create(int $projectId): View
    {
        $project = Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $types = ['character', 'location', 'lore', 'rule', 'timeline_event', 'note'];
        $importanceLevels = ['none', 'minor', 'important', 'critical'];

        return $this->view('canon.create', compact('project', 'types', 'importanceLevels'));
    }

    /**
     * Store new canon entry.
     */
    public function store(Request $request, int $projectId): RedirectResponse
    {
        $project = Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:character,location,lore,rule,timeline_event,note',
            'content' => 'nullable|string',
            'importance' => 'nullable|in:none,minor,important,critical',
            'tags' => 'nullable|array',
            'image' => 'nullable|string',
        ]);

        $entry = $project->canonEntries()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'importance' => $validated['importance'] ?? 'none',
            'tags' => $validated['tags'] ?? [],
            'image' => $validated['image'] ?? null,
        ]);

        return redirect()->route('canon.show', [$projectId, $entry->id])
            ->with('success', 'Canon entry created!');
    }

    /**
     * Display single canon entry.
     */
    public function show(int $projectId, int $id): View
    {
        $project = Project::findOrFail($projectId);
        $entry = $project->canonEntries()->findOrFail($id);

        return $this->view('canon.show', compact('project', 'entry'));
    }

    /**
     * Show edit form.
     */
    public function edit(int $projectId, int $id): View
    {
        $project = Project::findOrFail($projectId);
        $entry = $project->canonEntries()->findOrFail($id);

        $types = ['character', 'location', 'lore', 'rule', 'timeline_event', 'note'];
        $importanceLevels = ['none', 'minor', 'important', 'critical'];

        return $this->view('canon.edit', compact('project', 'entry', 'types', 'importanceLevels'));
    }

    /**
     * Update canon entry.
     */
    public function update(Request $request, int $projectId, int $id): RedirectResponse
    {
        $project = Project::findOrFail($projectId);
        $entry = $project->canonEntries()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:character,location,lore,rule,timeline_event,note',
            'content' => 'nullable|string',
            'importance' => 'nullable|in:none,minor,important,critical',
            'tags' => 'nullable|array',
            'image' => 'nullable|string',
        ]);

        $entry->update($validated);

        return redirect()->route('canon.show', [$projectId, $entry->id])
            ->with('success', 'Canon entry updated!');
    }

    /**
     * Delete canon entry.
     */
    public function destroy(int $projectId, int $id): RedirectResponse
    {
        $project = Project::findOrFail($projectId);
        $entry = $project->canonEntries()->findOrFail($id);

        $entry->delete();

        return redirect()->route('canon.index', $projectId)
            ->with('success', 'Canon entry deleted.');
    }
}
