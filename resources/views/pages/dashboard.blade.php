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
            <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Project</a>
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
                <p class="stat-value">3</p>
                <p class="muted">Active projects</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">💭</span>
                <h3>Sessions</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">12</p>
                <p class="muted">Total sessions</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">🎬</span>
                <h3>Outputs</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">48</p>
                <p class="muted">AI generations</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-icon">📚</span>
                <h3>Canon</h3>
            </div>
            <div class="card-body">
                <p class="stat-value">156</p>
                <p class="muted">Entries</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Projects -->
    <div class="section">
        <h2>Recent Projects</h2>
        <div class="projects-list">
            <div class="project-card">
                <div class="project-icon">🦸</div>
                <div class="project-info">
                    <h4>Boy Wonder</h4>
                    <p class="muted">Comic • 3 sessions</p>
                </div>
                <div class="project-progress">
                    <div class="progress-bar" style="width: 35%"></div>
                    <span class="progress-label">35%</span>
                </div>
            </div>
            
            <div class="project-card">
                <div class="project-icon">🌃</div>
                <div class="project-info">
                    <h4>Midnight Lagos</h4>
                    <p class="muted">Film • 2 sessions</p>
                </div>
                <div class="project-progress">
                    <div class="progress-bar" style="width: 20%"></div>
                    <span class="progress-label">20%</span>
                </div>
            </div>
            
            <div class="project-card">
                <div class="project-icon">⚡</div>
                <div class="project-info">
                    <h4>Electric Dreams</h4>
                    <p class="muted">Music Video • 1 session</p>
                </div>
                <div class="project-progress">
                    <div class="progress-bar" style="width: 10%"></div>
                    <span class="progress-label">10%</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="section">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <button class="btn btn-secondary">💭 New Session</button>
            <button class="btn btn-secondary">📷 Upload Reference</button>
            <button class="btn btn-secondary">📅 View Timeline</button>
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

.projects-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.project-card {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md);
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: border-color 150ms;
}

.project-card:hover {
    border-color: var(--accent-orange);
}

.project-icon {
    font-size: 2rem;
}

.project-info {
    flex: 1;
}

.project-info h4 {
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

.quick-actions {
    display: flex;
    gap: var(--space-md);
    flex-wrap: wrap;
}
</style>
@endsection