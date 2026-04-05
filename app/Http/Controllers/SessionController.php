<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Display a listing of sessions.
     */
    public function index()
    {
        return view('sessions.index');
    }

    /**
     * Display a specific session.
     */
    public function show($id)
    {
        return view('sessions.show', ['sessionId' => $id]);
    }

    /**
     * Show the form for creating a new session.
     */
    public function create()
    {
        return view('sessions.create');
    }

    /**
     * Store a newly created session.
     */
    public function store(Request $request)
    {
        return redirect()->back();
    }
}
