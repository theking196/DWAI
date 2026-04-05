<!-- ProjectCard Component -->
<div class="project-card {{ $attributes->get('class', '') }}" 
     {{ $attributes->only(['id', 'data-id']) }}
     @click="$attributes->get('click')">
    
    <div class="project-thumbnail">
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $title }}">
        @else
            <span class="thumbnail-icon">{!! $thumbnailIcon ?? '📁' !!}</span>
        @endif
    </div>
    
    <div class="project-info">
        <h3>{{ $title }}</h3>
        <p class="project-type">{{ $type ?? 'Project' }}</p>
        
        <div class="project-stats">
            <span>💭 {{ $sessionCount ?? 0 }}</span>
            <span>📚 {{ $canonCount ?? 0 }}</span>
            <span>🎬 {{ $outputCount ?? 0 }}</span>
        </div>
    </div>
    
    @if($progress !== null)
    <div class="project-progress">
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $progress }}%"></div>
        </div>
        <span class="progress-text">{{ $progress }}%</span>
    </div>
    @endif
</div>

<style>
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

.project-card.add-new {
    border-style: dashed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.project-card.add-new:hover {
    border-color: var(--accent-cyan);
}

.project-thumbnail {
    font-size: 3rem;
    margin-bottom: var(--space-md);
    text-align: center;
}

.project-thumbnail img {
    max-width: 100%;
    border-radius: var(--radius-md);
}

.thumbnail-icon {
    font-size: 3rem;
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

.progress-fill {
    height: 100%;
    background: var(--accent-orange);
}

.progress-text {
    font-size: 0.75rem;
    color: var(--text-muted);
    min-width: 35px;
}
</style>