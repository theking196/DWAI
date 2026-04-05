@extends('layouts.app')

@section('title', 'Session: Origin Story - DWAI Studio')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Projects</a> /
    <a href="{{ route('projects.show', 1) }}">Boy Wonder</a> /
    <span class="breadcrumb-item">Origin Story</span>
@endsection

@section('content')
<div class="page-content session-workspace">
    <!-- Session Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1 id="session-title">Origin Story</h1>
            <span class="session-type-badge" id="session-type">brainstorm</span>
        </div>
        <div class="workspace-stats">
            <span>💭 <span id="stat-prompt">12</span> prompts</span>
            <span>🎬 <span id="stat-output">5</span> outputs</span>
        </div>
    </div>
    
    <!-- Workspace Grid -->
    <div class="workspace-grid">
        <!-- Left Column: Short-Term Memory -->
        <div class="workspace-panel memory-panel">
            <div class="panel-header">
                <h3>🧠 Short-Term Memory</h3>
            </div>
            <div class="panel-body flex-column">
                <!-- Memory Tabs -->
                <div class="memory-tabs">
                    <button class="mem-tab active" data-mem-tab="notes">Notes</button>
                    <button class="mem-tab" data-mem-tab="drafts">Drafts</button>
                    <button class="mem-tab" data-mem-tab="refs">Refs</button>
                </div>
                
                <!-- Notes Tab -->
                <div class="mem-tab-content active" id="mem-notes">
                    <div class="memory-list" id="memory-list">
                        <div class="empty-state">No notes yet</div>
                    </div>
                    <div class="memory-input-area short">
                        <textarea class="form-input" id="add-memory-input" placeholder="Add note..."></textarea>
                        <button class="btn btn-primary btn-sm" id="add-memory-btn">Add</button>
                    </div>
                </div>
                
                <!-- Drafts Tab -->
                <div class="mem-tab-content" id="mem-drafts">
                    <div class="drafts-list" id="drafts-list">
                        <div class="empty-state">No drafts yet</div>
                    </div>
                    <div class="memory-input-area">
                        <textarea class="form-input" id="add-draft-input" placeholder="Add scene draft..."></textarea>
                        <button class="btn btn-primary btn-sm" id="add-draft-btn">Add</button>
                    </div>
                </div>
                
                <!-- References Tab -->
                <div class="mem-tab-content" id="mem-refs">
                    <input type="file" id="temp-ref-input" accept="image/*" hidden>
                    <div class="temp-refs-grid" id="temp-refs-grid">
                        <div class="empty-state">No temp refs</div>
                    </div>
                    <button class="btn btn-secondary btn-sm" id="add-ref-btn">+ Add Reference</button>
                </div>
            </div>
        </div>
        
        <!-- Center Column: AI Generate -->
        <div class="workspace-panel ai-panel">
            <div class="panel-header">
                <h3>🤖 AI Generate</h3>
                <select class="form-select" id="ai-model-select">
                    <option value="gpt-4">GPT-4</option>
                    <option value="claude">Claude</option>
                </select>
            </div>
            <div class="panel-body no-padding flex-column">
                <!-- Prompt Area -->
                <div class="ai-prompt-area">
                    <textarea class="form-input" id="ai-prompt-input" placeholder="Describe what you want to generate..."></textarea>
                    <div class="ai-prompt-actions">
                        <select class="form-select" id="ai-type-select">
                            <option value="text">Text</option>
                            <option value="image">Image</option>
                        </select>
                        <button class="btn btn-primary" id="ai-generate-btn">Generate</button>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div class="ai-loading" id="ai-loading">
                    <div class="loading-spinner"></div>
                    <p>Generating...</p>
                </div>
                
                <!-- Outputs -->
                <div class="ai-outputs" id="ai-outputs">
                    <div class="empty-state" id="no-outputs">No outputs yet. Enter a prompt and click Generate.</div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Conflicts + Visual Style -->
        <div class="workspace-panel info-panel">
            <!-- Conflicts -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3>⚠️ Conflicts</h3>
                    <button class="btn btn-ghost btn-sm" id="scan-conflicts-btn">🔍 Scan</button>
                </div>
                <div class="panel-body">
                    <div class="conflict-list" id="conflict-list">
                        <div class="conflict-item unresolved" data-conflict="timeline">
                            <span class="conflict-icon">⚠️</span>
                            <div class="conflict-content">
                                <strong>Timeline Mismatch</strong>
                                <p class="muted">Character age inconsistency in scenes 3-5</p>
                            </div>
                            <div class="conflict-actions">
                                <button class="btn btn-primary btn-sm">Resolve</button>
                                <button class="btn btn-ghost btn-sm">Dismiss</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Session Visual Style -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3>📷 Visual Style</h3>
                </div>
                <div class="panel-body">
                    <div class="style-preview">
                        <span class="style-placeholder">🎨</span>
                        <p class="muted">Inherits from project</p>
                    </div>
                    <button class="btn btn-secondary btn-sm w-full" id="update-style-btn">Update Style</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.session-workspace {
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

.session-type-badge {
    padding: 4px 12px;
    background: var(--accent-purple);
    color: white;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.workspace-stats {
    display: flex;
    gap: var(--space-lg);
    font-size: 0.875rem;
    color: var(--text-muted);
}

.workspace-grid {
    display: grid;
    grid-template-columns: 280px 1fr 280px;
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

.panel-body.no-padding {
    padding: 0;
}

.panel-body.flex-column {
    display: flex;
    flex-direction: column;
}

/* Memory Tabs */
.memory-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: var(--space-md);
}

.mem-tab {
    padding: 4px 8px;
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 0.75rem;
    cursor: pointer;
    border-radius: 4px;
}

.mem-tab:hover {
    background: var(--dark-surface);
}

.mem-tab.active {
    background: var(--accent-orange);
    color: white;
}

.mem-tab-content {
    display: none;
    flex-direction: column;
    flex: 1;
}

.mem-tab-content.active {
    display: flex;
}

.memory-list,
.drafts-list,
.temp-refs-grid {
    flex: 1;
    overflow-y: auto;
}

.empty-state {
    text-align: center;
    padding: var(--space-xl);
    color: var(--text-muted);
    font-size: 0.875rem;
}

.memory-input-area {
    display: flex;
    gap: var(--space-sm);
    padding-top: var(--space-md);
    border-top: 1px solid var(--panel-border);
}

.memory-input-area .form-input {
    flex: 1;
    min-height: 40px;
    resize: none;
}

/* AI Panel */
.ai-prompt-area {
    padding: var(--space-md);
    border-bottom: 1px solid var(--panel-border);
}

.ai-prompt-area .form-input {
    min-height: 80px;
    margin-bottom: var(--space-sm);
}

.ai-prompt-actions {
    display: flex;
    gap: var(--space-sm);
}

.ai-prompt-actions .form-select {
    width: 120px;
}

.ai-prompt-actions .btn {
    flex: 1;
}

.ai-loading {
    padding: var(--space-xl);
    text-align: center;
    display: none;
}

.loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--panel-border);
    border-top-color: var(--accent-orange);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto var(--space-md);
}

@keyframes spin { to { transform: rotate(360deg); } }

.ai-outputs {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-md);
}

.ai-output-item {
    background: var(--dark-surface);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-md);
    overflow: hidden;
}

.ai-output-item:last-child {
    margin-bottom: 0;
}

.ai-output-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-sm) var(--space-md);
    border-bottom: 1px solid var(--panel-border);
}

.ai-output-type {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--accent-cyan);
    text-transform: uppercase;
}

.ai-output-time {
    font-size: 0.6875rem;
    color: var(--text-muted);
}

.ai-output-content {
    padding: var(--space-md);
}

.ai-output-text {
    font-size: 0.875rem;
    line-height: 1.6;
    white-space: pre-wrap;
}

.ai-output-actions {
    display: flex;
    gap: var(--space-sm);
    padding: var(--space-sm) var(--space-md);
    border-top: 1px solid var(--panel-border);
}

/* Conflicts */
.conflict-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.conflict-item {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    border-left: 3px solid var(--error);
    background: rgba(239, 68, 68, 0.05);
}

.conflict-content strong {
    display: block;
    font-size: 0.875rem;
    margin-bottom: var(--space-xs);
}

.conflict-actions {
    display: flex;
    gap: var(--space-sm);
    margin-top: var(--space-sm);
}

/* Visual Style */
.style-preview {
    padding: var(--space-lg);
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
</style>
@endsection