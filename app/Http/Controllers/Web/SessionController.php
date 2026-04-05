<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Session;
use App\Models\AIOutput;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    /**
     * Display a listing of sessions.
     */
    public function index(): View
    {
        $sessions = Session::with('project')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return view('sessions.index', compact('sessions'));
    }

    /**
     * Display a specific session with AI outputs.
     */
    public function show(int $id): View
    {
        $session = Session::with([
            'project',
            'aiOutputs' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->findOrFail($id);
        
        $project = $session->project;
        $outputs = $session->aiOutputs;
        
        // Get project references for AI context
        $references = $project->referenceImages()
            ->select('id', 'title', 'path')
            ->get();
        
        // Stats for session
        $stats = [
            'prompt' => $session->output_count,
            'output' => $outputs->count(),
        ];
        
        return view('sessions.show', compact(
            'session',
            'project',
            'outputs',
            'references',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new session.
     */
    public function create(): View
    {
        $projects = Project::all();
        return view('sessions.create', compact('projects'));
    }

    /**
     * Store a newly created session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);
        
        $session = Session::create($validated);
        
        return redirect()->route('sessions.show', $session->id)
            ->with('success', 'Session created!');
    }
}
