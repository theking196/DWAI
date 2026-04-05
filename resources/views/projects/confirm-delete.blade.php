@extends('layouts.app')

@section('title', 'Delete Project - DWAI Studio')

@section('content')
<div class="page-content">
    <div class="delete-confirmation">
        <div class="warning-icon">⚠️</div>
        
        <h1>Delete Project?</h1>
        
        <p class="warning-text">
            This will <strong>permanently delete</strong> the project 
            <strong>"{{ $project->name }}"</strong> and all its contents.
        </p>
        
        <div class="project-stats">
            <h3>This will remove:</h3>
            <ul>
                <li>{{ $counts['sessions'] }} Sessions</li>
                <li>{{ $counts['canon_entries'] }} Canon Entries</li>
                <li>{{ $counts['reference_images'] }} Reference Images</li>
                <li>{{ $counts['timeline_events'] }} Timeline Events</li>
                <li>{{ $counts['conflicts'] }} Conflicts</li>
            </ul>
        </div>
        
        <form method="POST" action="{{ route('projects.destroy', $project->id) }}" class="confirm-form">
            @csrf
            @method('DELETE')
            
            <div class="form-group">
                <label for="project_name">
                    Type <strong>{{ $project->name }}</strong> to confirm deletion:
                </label>
                <input type="text" 
                       name="project_name" 
                       id="project_name"
                       required
                       autocomplete="off"
                       placeholder="Enter project name">
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" name="confirm_delete" id="confirm_delete" required>
                <label for="confirm_delete">Yes, I understand this is permanent</label>
            </div>
            
            <div class="form-actions">
                <a href="{{ route('projects.show', $project->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Delete Permanently</button>
            </div>
        </form>
    </div>
</div>

<style>
.delete-confirmation {
    max-width: 500px;
    margin: 50px auto;
    padding: var(--space-xl);
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
    text-align: center;
}

.warning-icon {
    font-size: 3rem;
    margin-bottom: var(--space-md);
}

.warning-text {
    color: var(--text-muted);
    margin-bottom: var(--space-lg);
}

.project-stats {
    background: var(--dark-surface);
    padding: var(--space-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-lg);
    text-align: left;
}

.project-stats h3 {
    font-size: 0.875rem;
    margin-bottom: var(--space-sm);
}

.project-stats ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.project-stats li {
    padding: 4px 0;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.confirm-form .form-group {
    margin-bottom: var(--space-md);
    text-align: left;
}

.confirm-form label {
    display: block;
    margin-bottom: var(--space-xs);
    font-size: 0.875rem;
}

.confirm-form input[type="text"] {
    width: 100%;
    padding: var(--space-sm);
    background: var(--dark-surface);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    color: var(--text-primary);
}

.confirm-form .checkbox {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.confirm-form .checkbox input {
    width: auto;
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: center;
    margin-top: var(--space-lg);
}

.btn-danger {
    background: var(--error);
    color: white;
    border: none;
    padding: var(--space-sm) var(--space-lg);
    border-radius: var(--radius-md);
    cursor: pointer;
}

.btn-danger:hover {
    background: #dc2626;
}
</style>
@endsection
