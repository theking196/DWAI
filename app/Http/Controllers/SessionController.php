<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        return view('pages.sessions');
    }

    public function show(string $id)
    {
        return view('pages.session-workspace', ['id' => $id]);
    }
}