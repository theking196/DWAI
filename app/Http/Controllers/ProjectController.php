<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index()
    {
        return view('projects.index');
    }

    /**
     * Display a specific project.
     */
    public function show($id)
    {
        // Pass project ID to view
        return view('projects.show', ['projectId' => $id]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        // Handle project creation
        return redirect()->route('projects.index');
    }

    /**
     * Remove the specified project.
     */
    public function destroy($id)
    {
        // Handle project deletion
        return redirect()->route('projects.index');
    }
}
