@extends('layouts.app')

@section('title', 'New Session - DWAI Studio')

@section('content')
<div class="page-content">
    <div class="form-container">
        <h1>Create New Session</h1>
        
        <form action="{{ route('sessions.store') }}" method="POST" class="session-form">
            @csrf
            
            <input type="hidden" name="project_id" value="{{ $preSelectedProject }}">
            
            <div class="form-group">
                <label for="name">Session Name</label>
                <input type="text" name="name" id="name" class="form-input" required placeholder="My new session...">
            </div>
            
            <div class="form-group">
                <label for="description">Description (optional)</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="What's this session about?"></textarea>
            </div>
            
            <div class="form-group">
                <label for="type">Session Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="brainstorm">Brainstorm</option>
                    <option value="script">Script</option>
                    <option value="storyboard">Storyboard</option>
                    <option value="edit">Edit</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="session_type">Session Mode</label>
                <select name="session_type" id="session_type" class="form-select">
                    <option value="normal">Normal</option>
                    <option value="assistant">Assistant Agent</option>
                    <option value="progressive">Progressive Build</option>
                </select>
                <p class="form-help">Choose how you want to work with AI in this session.</p>
            </div>
            
            <div class="form-actions">
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Session</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: var(--space-xl);
}

.form-group {
    margin-bottom: var(--space-lg);
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--space-sm);
}

.form-help {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: var(--space-xs);
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    margin-top: var(--space-xl);
}
</style>
@endsection
