@extends('layouts.app')

@section('title', 'Dashboard - DWAI Studio')

@section('breadcrumb')
    <span class="breadcrumb-item">Dashboard</span>
@endsection

@section('content')
<div class="page-content">
    <div class="dashboard-header">
        <div>
            <h1>Welcome to DWAI Studio</h1>
            <p class="muted">Your cinematic intelligence workspace</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('dwai.projects.create') }}" class="btn btn-primary">+ New Project</a>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Stats Cards -->
        <div class="card">
            <div class="card-header">
                <span class="card-icon">📁</span>
                <h3>Projects</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">{{ $stats['projects'] ?? 0 }}</p>
                <p class="muted">Active projects</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">💭</span>
                <h3>Sessions</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">{{ $stats['active_sessions'] ?? 0 }}</p>
                <p class="muted">Active sessions</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">🎬</span>
                <h3>Outputs</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">{{ $stats['outputs'] ?? 0 }}</p>
                <p class="muted">AI generations</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">📚</span>
                <h3>Canon</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">{{ $stats['canon'] ?? 0 }}</p>
                <p class="muted">Entries</p>
            </div>
        </div>
    </div>
    
    @if($recentProjects->count() > 0)
    <!-- Recent Projects -->
    <div class="section">
        <h2>Recent Projects</h2>
        <div class="projects-list">
            @foreach($recentProjects as $project)
            <a href="{{ route('dwai.projects.show', $project->id) }}" class="project-card">
                <div class="project-icon">{{ $project->type === 'story' ? '📖' : ($project->type === 'novel' ? '📚' : ($project->type === 'script' ? '🎬' : '🌍')) }}</div>
                <div class="project-info">
                    <h4>{{ $project->name }}</h4>
                    <p class="muted">{{ ucfirst($project->type) }} • {{ $project->sessions_count ?? $project->sessions()->count() }} sessions</p>
                </div>
                <div class="project-progress">
                    <div class="progress-bar" style="width: {{ $project->progress ?? 0 }}%"></div>
                    <span class="progress-label">{{ $project->progress ?? 0 }}%</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    
    @if($recentSessions->count() > 0)
    <!-- Active Sessions -->
    <div class="section">
        <h2>Active Sessions</h2>
        <div class="sessions-list">
            @foreach($recentSessions as $session)
            <a href="{{ route('dwai.sessions.show', $session->id) }}" class="session-card">
                <div class="session-info">
                    <h4>{{ $session->name }}</h4>
                    <p class="muted">{{ $session->project->name ?? 'Unknown Project' }}</p>
                </div>
                <span class="session-status">{{ $session->status }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    
    @if($recentActivity->count() > 0)
    <!-- Recent Activity -->
    <div class="section">
        <h2>Recent Activity</h2>
        <div class="activity-list">
            @foreach($recentActivity as $activity)
            <div class="activity-item">
                <span class="activity-icon">
                    @switch($activity->event_type)
                        @case('project.created') 📁 @break
                        @case('session.started') 💭 @break
                        @case('canon.edited') 📝 @break
                        @case('reference.uploaded') 🖼️ @break
                        @case('output.generated') 🎬 @break
                        @default 📋
                    @endswitch
                </span>
                <div class="activity-content">
                    <p>{{ $activity->description }}</p>
                    <span class="activity-time">{{ $activity->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Quick Actions -->
    <div class="section">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <a href="{{ route('dwai.projects.index') }}" class="btn btn-secondary">📁 View Projects</a>
            <button class="btn btn-secondary" onclick="document.getElementById('search-modal').showModal()">🔍 Search</button>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-xl);
}

.dashboard-header h1 {
    font-size: 1.75rem;
    margin-bottom: var(--space-xs);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-md);
    margin-bottom: var(--space-xl);
}

.card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
}

.card-header {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-bottom: var(--space-sm);
}

.card-icon {
    font-size: 1.25rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-orange);
}

.section {
    margin-bottom: var(--space-xl);
}

.section h2 {
    font-size: 1.25rem;
    margin-bottom: var(--space-md);
}

.projects-list, .sessions-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.project-card, .session-card {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md);
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: border-color 150ms;
    text-decoration: none;
    color: inherit;
}

.project-card:hover, .session-card:hover {
    border-color: var(--accent-orange);
}

.project-icon {
    font-size: 2rem;
}

.project-info, .session-info {
    flex: 1;
}

.project-info h4, .session-info h4 {
    font-size: 1rem;
    margin-bottom: var(--space-xs);
}

.project-progress {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    width: 100px;
}

.progress-bar {
    height: 4px;
    background: var(--accent-orange);
    border-radius: 2px;
}

.progress-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    min-width: 35px;
}

.session-status {
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    background: var(--accent-green);
    font-size: 0.75rem;
    text-transform: capitalize;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.activity-item {
    display: flex;
    gap: var(--space-md);
    padding: var(--space-sm) 0;
    border-bottom: 1px solid var(--panel-border);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.25rem;
}

.activity-content p {
    margin: 0;
    font-size: 0.875rem;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.quick-actions {
    display: flex;
    gap: var(--space-md);
    flex-wrap: wrap;
}
</style>
@endsection
