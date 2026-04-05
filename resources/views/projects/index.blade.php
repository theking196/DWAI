@extends('layouts.app')

@section('title', 'Projects - DWAI Studio')

@section('breadcrumb')
    <span class="breadcrumb-item">Projects</span>
@endsection

@section('content')
<div class="page-content projects-page">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Projects</h1>
        <div class="page-actions">
            <div class="view-toggle">
                <button class="btn btn-ghost active" data-view="grid" title="Grid view">▦</button>
                <button class="btn btn-ghost" data-view="list" title="List view">☰</button>
            </div>
            <button class="btn btn-primary" id="new-project-btn">+ New Project</button>
            <button class="btn btn-secondary" id="import-sample-btn">Import Sample</button>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="filters-bar">
        <input type="text" class="form-input search-input" placeholder="Search projects..." id="project-search">
        <select class="form-select" id="type-filter">
            <option value="">All Types</option>
            <option value="comic">Comic</option>
            <option value="film">Film</option>
            <option value="music">Music Video</option>
        </select>
    </div>
    
    <!-- Projects Grid -->
    <div class="projects-grid" id="projects-grid">
        <!-- Sample Project Card -->
        <div class="project-card" data-id="1700001">
            <div class="project-thumbnail">🦸</div>
            <div class="project-info">
                <h3>Boy Wonder</h3>
                <p class="project-type">Comic • Lagos superhero</p>
                <div class="project-stats">
                    <span>💭 3</span>
                    <span>📚 8</span>
                    <span>🎬 12</span>
                </div>
            </div>
            <div class="project-progress">
                <div class="progress-bar" style="width: 35%"></div>
                <span class="progress-text">35%</span>
            </div>
        </div>
        
        <!-- Add New Card -->
        <div class="project-card add-new" id="add-project-card">
            <div class="add-icon">+</div>
            <p>Create New Project</p>
        </div>
    </div>
</div>

<style>
.projects-page {
    padding: var(--space-lg);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-lg);
}

.page-header h1 {
    font-size: 1.75rem;
}

.page-actions {
    display: flex;
    gap: var(--space-md);
    align-items: center;
}

.view-toggle {
    display: flex;
    background: var(--dark-surface);
    border-radius: var(--radius-md);
    padding: 2px;
}

.view-toggle .btn {
    padding: var(--space-sm);
}

.view-toggle .btn.active {
    background: var(--panel-bg);
    color: var(--accent-orange);
}

.filters-bar {
    display: flex;
    gap: var(--space-md);
    margin-bottom: var(--space-lg);
}

.filters-bar .search-input {
    flex: 1;
    max-width: 300px;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.project-card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    cursor: pointer;
    transition: transform 150ms, border-color 150ms;
}

.project-card:hover {
    transform: translateY(-2px);
    border-color: var(--accent-orange);
}

.project-thumbnail {
    font-size: 3rem;
    margin-bottom: var(--space-md);
    text-align: center;
}

.project-info h3 {
    font-size: 1.125rem;
    margin-bottom: var(--space-xs);
}

.project-type {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: var(--space-sm);
}

.project-stats {
    display: flex;
    gap: var(--space-md);
    font-size: 0.75rem;
    color: var(--text-muted);
}

.project-progress {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-top: var(--space-md);
}

.progress-bar {
    flex: 1;
    height: 4px;
    background: var(--panel-border);
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar::before {
    content: '';
    display: block;
    width: 35%;
    height: 100%;
    background: var(--accent-orange);
}

.progress-text {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.add-new {
    border-style: dashed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.add-new:hover {
    border-color: var(--accent-cyan);
}

.add-new .add-icon {
    font-size: 2rem;
    color: var(--text-muted);
    margin-bottom: var(--space-sm);
}

.add-new p {
    color: var(--text-muted);
}
</style>
@endsection