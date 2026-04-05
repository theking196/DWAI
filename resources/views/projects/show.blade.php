@extends('layouts.app')

@section('title', 'Project: Boy Wonder - DWAI Studio')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Projects</a>
    <span class="separator">/</span>
    <span class="breadcrumb-item">Boy Wonder</span>
@endsection

@section('content')
<div class="page-content project-workspace">
    <!-- Project Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1 id="project-title">Boy Wonder</h1>
            <span class="project-type-badge" id="project-type">Comic</span>
        </div>
        <div class="workspace-actions">
            <button class="btn btn-secondary" id="settings-btn">⚙️ Settings</button>
            <button class="btn btn-primary" id="new-session-btn">+ New Session</button>
        </div>
    </div>
    
    <!-- Workspace Grid -->
    <div class="workspace-grid">
        <!-- Left Column: Sessions -->
        <div class="workspace-panel sessions-panel">
            <div class="panel-header">
                <h3>💭 Sessions</h3>
            </div>
            <div class="panel-body">
                <div class="session-list">
                    <div class="session-item active" data-id="1800001">
                        <div class="session-icon">💭</div>
                        <div class="session-info">
                            <h4>Origin Story</h4>
                            <p class="muted">brainstorm • 5 outputs</p>
                        </div>
                    </div>
                    <div class="session-item" data-id="1800002">
                        <div class="session-icon">📝</div>
                        <div class="session-info">
                            <h4>First Battle</h4>
                            <p class="muted">script • 4 outputs</p>
                        </div>
                    </div>
                    <div class="session-item" data-id="1800003">
                        <div class="session-icon">🎬</div>
                        <div class="session-info">
                            <h4>Team Formation</h4>
                            <p class="muted">storyboard • 3 outputs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Center Column: Canon Library -->
        <div class="workspace-panel canon-panel">
            <div class="panel-header">
                <div class="canon-tabs">
                    <button class="canon-tab active" data-tab="entries">Canon Entries</button>
                    <button class="canon-tab" data-tab="references">Reference Images</button>
                </div>
            </div>
            <div class="panel-body">
                <!-- Canon Entries Tab -->
                <div class="canon-tab-content active" id="canon-entries">
                    <div class="canon-list">
                        <div class="canon-entry" data-id="1900001">
                            <span class="canon-icon">⚡</span>
                            <div class="canon-content">
                                <h4>The Lightning Strike</h4>
                                <p class="type-badge">event</p>
                            </div>
                        </div>
                        <div class="canon-entry" data-id="1900002">
                            <span class="canon-icon">📜</span>
                            <div class="canon-content">
                                <h4>Power Level System</h4>
                                <p class="type-badge">rule</p>
                            </div>
                        </div>
                        <div class="canon-entry" data-id="1900003">
                            <span class="canon-icon">😈</span>
                            <div class="canon-content">
                                <h4>The Shadow King</h4>
                                <p class="type-badge">character</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- References Tab -->
                <div class="canon-tab-content" id="canon-references">
                    <div class="canon-toolbar">
                        <button class="btn btn-ghost btn-sm">▦ Gallery</button>
                        <button class="btn btn-primary btn-sm">+ Upload Image</button>
                    </div>
                    <div class="reference-grid">
                        <div class="reference-card">🦸</div>
                        <div class="reference-card">😈</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Visual Style + Timeline -->
        <div class="workspace-panel info-panel">
            <!-- Visual Style -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3>📷 Visual Style</h3>
                </div>
                <div class="panel-body">
                    <div class="style-preview">
                        <span class="style-placeholder">🎨</span>
                        <p class="muted">Inherits from project</p>
                    </div>
                    <button class="btn btn-secondary btn-sm w-full">Update Style</button>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3>📅 Timeline</h3>
                    <button class="btn btn-ghost btn-sm">+ Add</button>
                </div>
                <div class="panel-body">
                    <div class="timeline-container">
                        <div class="timeline-event">
                            <div class="timeline-node"></div>
                            <div class="timeline-event-label">Project Started</div>
                            <div class="timeline-event-time">2026-03-15</div>
                        </div>
                        <div class="timeline-event">
                            <div class="timeline-node"></div>
                            <div class="timeline-event-label">Characters</div>
                            <div class="timeline-event-time">2026-03-20</div>
                        </div>
                        <div class="timeline-event">
                            <div class="timeline-node"></div>
                            <div class="timeline-event-label">Script</div>
                            <div class="timeline-event-time">2026-03-25</div>
                        </div>
                        <div class="timeline-event">
                            <div class="timeline-node"></div>
                            <div class="timeline-event-label">AI Images</div>
                            <div class="timeline-event-time">2026-04-01</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.project-workspace {
    padding: var(--space-lg);
}

.workspace-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-lg);
}

.workspace-title {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.workspace-title h1 {
    font-size: 1.5rem;
}

.project-type-badge {
    padding: 4px 12px;
    background: var(--accent-cyan);
    color: var(--dark-bg);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.workspace-actions {
    display: flex;
    gap: var(--space-md);
}

.workspace-grid {
    display: grid;
    grid-template-columns: 280px 1fr 300px;
    gap: var(--space-lg);
    flex: 1;
    min-height: 0;
}

.workspace-panel {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-md) var(--space-lg);
    border-bottom: 1px solid var(--panel-border);
}

.panel-header h3 {
    font-size: 0.9375rem;
    font-weight: 600;
}

.panel-body {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-md);
}

/* Sessions */
.session-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.session-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-sm);
    border-radius: var(--radius-md);
    cursor: pointer;
}

.session-item:hover,
.session-item.active {
    background: var(--dark-surface);
}

.session-item.active {
    border-left: 2px solid var(--accent-orange);
}

.session-icon {
    font-size: 1.25rem;
}

.session-info h4 {
    font-size: 0.875rem;
    margin-bottom: 2px;
}

/* Canon */
.canon-tabs {
    display: flex;
    gap: var(--space-sm);
}

.canon-tab {
    padding: var(--space-xs) var(--space-sm);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 0.75rem;
}

.canon-tab.active {
    color: var(--accent-orange);
    border-bottom: 2px solid var(--accent-orange);
}

.canon-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.canon-entry {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-sm);
    background: var(--dark-surface);
    border-radius: var(--radius-md);
    cursor: pointer;
}

.canon-icon {
    font-size: 1.25rem;
}

.canon-content h4 {
    font-size: 0.875rem;
}

.type-badge {
    font-size: 0.625rem;
    color: var(--accent-cyan);
    text-transform: uppercase;
}

/* Reference */
.reference-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-sm);
}

.reference-card {
    aspect-ratio: 1;
    background: var(--dark-surface);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Visual Style */
.style-preview {
    padding: var(--space-xl);
    text-align: center;
    background: var(--dark-surface);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-md);
}

.style-placeholder {
    font-size: 2rem;
    display: block;
    margin-bottom: var(--space-sm);
}

/* Timeline */
.timeline-container {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
    padding-left: var(--space-md);
    border-left: 2px solid var(--panel-border);
}

.timeline-event {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.timeline-node {
    position: absolute;
    left: calc(-1 * var(--space-md) - 6px);
    width: 10px;
    height: 10px;
    background: var(--accent-orange);
    border-radius: 50%;
}

.timeline-event-label {
    font-size: 0.8125rem;
}

.timeline-event-time {
    font-size: 0.6875rem;
    color: var(--text-muted);
}
</style>
@endsection