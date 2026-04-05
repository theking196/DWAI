<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of all projects.
     */
    public function index(): View
    {
        $projects = Project::orderBy('updated_at', 'desc')->get();
        
        return view('projects.index', compact('projects'));
    }

    /**
     * Display a specific project with sessions and canon.
     */
    public function show(int $id): View
    {
        $project = Project::with([
            'sessions' => fn($q) => $q->orderBy('created_at', 'desc'),
            'canonEntries' => fn($q) => $q->orderBy('created_at', 'desc'),
            'referenceImages' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->findOrFail($id);
        
        $sessions = $project->sessions;
        $canon = $project->canonEntries;
        $references = $project->referenceImages;
        
        // Get timeline from sessions (ordered by created_at)
        $timeline = $sessions->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->name,
            'timestamp' => $s->created_at->toDateString(),
            'type' => 'session',
        ])->toArray();
        
        return view('projects.show', compact(
            'project',
            'sessions',
            'canon',
            'references',
            'timeline'
        ));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
        return view('projects.create');
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
        ]);
        
        $project = Project::create($validated);
        
        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(int $id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
        
        return redirect()->route('projects.index')
            ->with('success', 'Project deleted!');
    }
}
