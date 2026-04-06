@extends('layouts.app')

@section('title', 'Session: ' . ($session->name ?? 'Session') . ' - DWAI Studio')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Projects</a> /
    <a href="{{ route('projects.show', $project->id) }}">{{ $project->name }}</a> /
    <span class="breadcrumb-item">{{ $session->name ?? 'Session' }}</span>
@endsection

@section('content')
<div class="page-content session-workspace">
    <!-- Session Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1 id="session-title">{{ $session->name }}</h1>
            <span class="session-type-badge" id="session-type">{{ $session->type }}</span>
        </div>
    <!-- Short-Term Memory -->
    <div class="memory-bar" id="memory-bar">
        <div class="memory-section">
            <span class="memory-label">📝 Notes</span>
            <span class="memory-content" id="mem-notes">{{ $memory["temp_notes"] ?? "No notes" }}</span>
        </div>
        <div class="memory-section">
            <span class="memory-label">🤔 AI Plan</span>
            <span class="memory-content" id="mem-reasoning">{{ $memory["has_ai_reasoning"] ? "Active" : "None" }}</span>
        </div>
        <div class="memory-section">
            <span class="memory-label">📄 Draft</span>
            <span class="memory-content" id="mem-draft">{{ $memory["has_draft"] ? "In progress" : "Empty" }}</span>
        </div>
        <div class="memory-section">
            <span class="memory-label">🔗 Refs</span>
            <span class="memory-content" id="mem-refs-count">{{ $memory["reference_count"] ?? 0 }} refs</span>
        </div>
    </div>
        <div class="workspace-stats">
            <span>💭 <span id="stat-prompt">{{ $session->output_count }}</span> prompts</span>
            <span>🎬 <span id="stat-output">{{ $outputs->count() }}</span> outputs</span>
        </div>
    </div>
    
    <!-- Workspace Grid -->
    <div class="workspace-grid">
        <!-- Left Column: AI Generator -->
        <div class="workspace-panel generator-panel">
            <div class="panel-header">
                <h3>🤖 AI Generator</h3>
                <span class="provider-badge">Mock AI</span>
            </div>
            <div class="panel-body flex-column">
                <!-- Generation Type Tabs -->
                <div class="gen-type-tabs">
                    <button class="gen-tab active" data-type="text">💭 Text</button>
                    <button class="gen-tab" data-type="image">🎨 Image</button>
                </div>
                
                <!-- Prompt Input -->
                <form id="ai-generate-form" class="ai-form">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <input type="hidden" name="type" value="text">
                    
                    <div class="prompt-input-area">
                        <textarea 
                            name="prompt" 
                            class="form-input" 
                            id="prompt-input"
                            placeholder="Describe what you want AI to generate..."
                            rows="4"
                        ></textarea>
                    </div>
                    
                    <div class="gen-options">
                        <select name="model" class="form-select">
                            <option value="gpt-4">GPT-4</option>
                            <option value="gpt-3.5">GPT-3.5</option>
                        </select>
                        <button type="submit" class="btn btn-primary" id="generate-btn">
                            ⚡ Generate
                        </button>
                    </div>
                </form>
                
                <!-- Status Indicator -->
                <div class="gen-status" id="gen-status" style="display:none">
                    <div class="status-spinner"></div>
                    <span>Generating...</span>
                </div>
                
                <!-- Outputs -->
                <div class="ai-outputs" id="ai-outputs">
                    @forelse($outputs as $output)
                    <div class="output-card" data-id="{{ $output->id }}" data-status="{{ $output->status }}">
                        <div class="output-header">
                            <span class="output-type">{{ $output->type === 'image' ? '🎨' : '💭' }}</span>
                            <span class="output-model">{{ $output->model }}</span>
                            <span class="output-status {{ $output->status }}">{{ $output->status }}</span>
                        </div>
                        <div class="output-content">
                            @if($output->type === 'image')
                            <img src="{{ $output->result }}" alt="AI Generated">
                            @else
                            <p>{{ $output->result }}</p>
                            @endif
                        </div>
                        <div class="output-meta">
                            <span>{{ $output->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state" id="no-outputs">No outputs yet. Enter a prompt and click Generate.</div>
                    @endforelse
                </div>
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
        
        <!-- Right Column: Conflicts + Visual Style + Assistant -->
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
        
        <!-- Assistant Agent Panel -->
        <div class="workspace-panel assistant-panel" id="assistant-panel">
            <div class="panel-header">
                <h3>🎬 Assistant Agent</h3>
                <span class="phase-badge" id="phase-badge">{{ $session->assistant_phase ?? 'idea_input' }}</span>
            </div>
            <div class="panel-body flex-column">
                <!-- Phase Indicator -->
                <div class="phase-indicator" id="phase-indicator">
                    <div class="phase-step" data-phase="idea_input">1. Idea</div>
                    <div class="phase-step" data-phase="idea_refinement">2. Refine</div>
                    <div class="phase-step" data-phase="structure_planning">3. Structure</div>
                    <div class="phase-step" data-phase="image_prompts">4. Images</div>
                    <div class="phase-step" data-phase="video_prompts">5. Video</div>
                    <div class="phase-step" data-phase="music_prompt">6. Music</div>
                    <div class="phase-step" data-phase="complete">✓ Done</div>
                </div>
                
                <!-- Chat Interface -->
                <div class="assistant-chat" id="assistant-chat">
                    <div class="chat-messages" id="chat-messages">
                        <div class="chat-message assistant">
                            <span class="msg-icon">🎬</span>
                            <div class="msg-content">Welcome to Assistant Agent! Drop your idea below and I'll help create your visual story.</div>
                        </div>
                    </div>
                </div>
                
                <!-- Input -->
                <div class="assistant-input">
                    <textarea 
                        id="assistant-input" 
                        class="form-input"
                        placeholder="Drop your idea here..."
                        rows="3"
                    ></textarea>
                    <button class="btn btn-primary" id="assistant-send-btn">Send</button>
                </div>
                
                <!-- Outputs -->
                <div class="assistant-outputs" id="assistant-outputs">
                    <!-- Structure -->
                    @if($session->assistant_structure)
                    <div class="output-section" id="output-structure">
                        <h4>📋 Structured Breakdown</h4>
                        <pre>{{ json_encode($session->assistant_structure, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif
                    
                    <!-- Image Prompts -->
                    @if($session->assistant_image_prompts)
                    <div class="output-section" id="output-images">
                        <h4>🎨 Image Prompts ({{ count(json_decode($session->assistant_image_prompts, true) ?? []) }})</h4>
                        <ul class="prompt-list">
                            @foreach(json_decode($session->assistant_image_prompts, true) ?? [] as $prompt)
                            <li>{{ $prompt }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <!-- Video Prompts -->
                    @if($session->assistant_video_prompts)
                    <div class="output-section" id="output-video">
                        <h4>🎬 Video Prompts ({{ count(json_decode($session->assistant_video_prompts, true) ?? []) }})</h4>
                        <ul class="prompt-list">
                            @foreach(json_decode($session->assistant_video_prompts, true) ?? [] as $prompt)
                            <li>{{ $prompt }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <!-- Music Prompt -->
                    @if($session->assistant_music_prompt)
                    <div class="output-section" id="output-music">
                        <h4>🎵 Music Prompt</h4>
                        <p>{{ $session->assistant_music_prompt }}</p>
                    </div>
                    @endif
                </div>
                
                <!-- Actions -->
                <div class="assistant-actions">
                    <button class="btn btn-success" id="generate-pack-btn">Generate Full Production Pack</button>
                    <button class="btn btn-secondary" id="save-canon-btn">Save to Canon</button>
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
/* Assistant Agent Panel */
.assistant-panel {
    grid-column: span 3;
}

.phase-indicator {
    display: flex;
    gap: var(--space-xs);
    padding: var(--space-sm);
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
    overflow-x: auto;
    margin-bottom: var(--space-md);
}

.phase-step {
    font-size: 0.75rem;
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-sm);
    white-space: nowrap;
    opacity: 0.5;
}

.phase-step.active {
    opacity: 1;
    background: var(--primary);
    color: white;
}

.phase-step.completed {
    opacity: 1;
    background: var(--success);
    color: white;
}

.phase-badge {
    font-size: 0.75rem;
    padding: var(--space-xs) var(--space-sm);
    background: var(--bg-tertiary);
    border-radius: var(--radius-sm);
}

.assistant-chat {
    flex: 1;
    min-height: 200px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-md);
}

.chat-messages {
    padding: var(--space-md);
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.chat-message {
    display: flex;
    gap: var(--space-sm);
    align-items: flex-start;
}

.chat-message.user {
    flex-direction: row-reverse;
    text-align: right;
}

.msg-icon {
    font-size: 1.25rem;
}

.msg-content {
    padding: var(--space-sm) var(--space-md);
    border-radius: var(--radius-md);
    background: var(--bg-tertiary);
    max-width: 80%;
}

.chat-message.user .msg-content {
    background: var(--primary);
    color: white;
}

.chat-message.assistant .msg-content {
    background: var(--bg-secondary);
}

.assistant-input {
    display: flex;
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.assistant-input textarea {
    flex: 1;
    resize: none;
}

.assistant-outputs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-md);
    margin-bottom: var(--space-md);
}

.output-section {
    padding: var(--space-md);
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
}

.output-section h4 {
    font-size: 0.875rem;
    margin-bottom: var(--space-sm);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.output-section pre {
    font-size: 0.75rem;
    max-height: 100px;
    overflow: auto;
    background: var(--bg-primary);
    padding: var(--space-sm);
    border-radius: var(--radius-sm);
}

.prompt-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.75rem;
}

.prompt-list li {
    padding: var(--space-xs) 0;
    border-bottom: 1px solid var(--panel-border);
}

.assistant-actions {
    display: flex;
    gap: var(--space-sm);
}

.assistant-actions .btn {
    flex: 1;
}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = {{ $session->id }};
    const chatMessages = document.getElementById('chat-messages');
    const assistantInput = document.getElementById('assistant-input');
    const sendBtn = document.getElementById('assistant-send-btn');
    const phaseBadge = document.getElementById('phase-badge');
    const phaseIndicator = document.getElementById('phase-indicator');
    
    // Update phase indicator
    function updatePhaseIndicator(phase) {
        phaseBadge.textContent = phase;
        document.querySelectorAll('.phase-step').forEach(step => {
            step.classList.remove('active', 'completed');
            if (step.dataset.phase === phase) {
                step.classList.add('active');
            } else {
                const phases = ['idea_input', 'idea_refinement', 'structure_planning', 'image_prompts', 'video_prompts', 'music_prompt', 'complete'];
                if (phases.indexOf(step.dataset.phase) < phases.indexOf(phase)) {
                    step.classList.add('completed');
                }
            }
        });
    }
    
    // Add message to chat
    function addMessage(content, isUser = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-message ${isUser ? 'user' : 'assistant'}`;
        msgDiv.innerHTML = `
            <span class="msg-icon">${isUser ? '👤' : '🎬'}</span>
            <div class="msg-content">${content}</div>
        `;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Send message to assistant
    async function sendToAssistant(input) {
        try {
            const response = await fetch(`/api/dwai/assistant/${sessionId}/handle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ input: input })
            });
            
            const data = await response.json();
            
            if (data.current_phase) {
                updatePhaseIndicator(data.current_phase);
            }
            
            if (data.message) {
                addMessage(data.message, false);
            }
            
            if (data.generated_outputs) {
                updateOutputs(data.generated_outputs);
            }
            
            return data;
        } catch (error) {
            console.error('Assistant error:', error);
            addMessage('Error connecting to Assistant. Please try again.', false);
        }
    }
    
    // Update outputs display
    function updateOutputs(outputs) {
        const container = document.getElementById('assistant-outputs');
        let html = '';
        
        if (outputs.structure) {
            html += `<div class="output-section"><h4>📋 Structured Breakdown</h4><pre>${JSON.stringify(outputs.structure, null, 2)}</pre></div>`;
        }
        if (outputs.image_prompts && outputs.image_prompts.length) {
            html += `<div class="output-section"><h4>🎨 Image Prompts (${outputs.image_prompts.length})</h4><ul class="prompt-list">${outputs.image_prompts.map(p => `<li>${p}</li>`).join('')}</ul></div>`;
        }
        if (outputs.video_prompts && outputs.video_prompts.length) {
            html += `<div class="output-section"><h4>🎬 Video Prompts (${outputs.video_prompts.length})</h4><ul class="prompt-list">${outputs.video_prompts.map(p => `<li>${p}</li>`).join('')}</ul></div>`;
        }
        if (outputs.music_prompt) {
            html += `<div class="output-section"><h4>🎵 Music Prompt</h4><p>${outputs.music_prompt}</p></div>`;
        }
        
        if (html) {
            container.innerHTML = html;
        }
    }
    
    // Send button click
    sendBtn.addEventListener('click', async () => {
        const input = assistantInput.value.trim();
        if (!input) return;
        
        addMessage(input, true);
        assistantInput.value = '';
        
        await sendToAssistant(input);
    });
    
    // Enter key to send
    assistantInput.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendBtn.click();
        }
    });
    
    // Generate Full Production Pack
    document.getElementById('generate-pack-btn').addEventListener('click', async () => {
        const btn = document.getElementById('generate-pack-btn');
        btn.disabled = true;
        btn.textContent = 'Generating...';
        
        const phases = ['structure_planning', 'image_prompts', 'video_prompts', 'music_prompt'];
        
        for (const phase of phases) {
            await sendToAssistant('proceed');
        }
        
        btn.disabled = false;
        btn.textContent = 'Generate Full Production Pack';
    });
    
    // Save to Canon
    document.getElementById('save-canon-btn').addEventListener('click', async () => {
        const btn = document.getElementById('save-canon-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        try {
            const stateRes = await fetch(`/api/dwai/assistant/${sessionId}/state`);
            const state = await stateRes.json();
            
            if (state.structure) {
                const canonRes = await fetch('/api/dwai/canon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        title: 'Assistant Structure',
                        type: 'structure',
                        content: JSON.stringify(state.structure),
                        importance: 'important'
                    })
                });
                
                if (canonRes.ok) {
                    addMessage('✅ Structure saved to Canon!', false);
                }
            }
        } catch (error) {
            console.error('Save to canon error:', error);
        }
        
        btn.disabled = false;
        btn.textContent = 'Save to Canon';
    });
    
    // Initialize phase
    updatePhaseIndicator('{{ $session->assistant_phase ?? "idea_input" }}');
});
</script>
