<!-- SessionCard Component -->
<div class="session-item {{ $attributes->get('class', '') }} {{ $active ? 'active' : '' }}" 
     {{ $attributes->only(['id', 'data-id']) }}>
    
    <div class="session-icon">
        @switch($type ?? 'brainstorm')
            @case('brainstorm')
                💭
                @break
            @case('script')
                📝
                @break
            @case('storyboard')
                🎬
                @break
            @case('edit')
                ✂️
                @break
            @default
                💭
        @endswitch
    </div>
    
    <div class="session-info">
        <h4>{{ $title }}</h4>
        <p class="muted">{{ $type ?? 'brainstorm' }} • {{ $outputCount ?? 0 }} outputs</p>
    </div>
    
    @if($status)
    <span class="session-status {{ $status }}">{{ $status }}</span>
    @endif
</div>

<style>
.session-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-sm);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background 150ms;
}

.session-item:hover {
    background: var(--dark-surface);
}

.session-item.active {
    background: var(--dark-surface);
    border-left: 2px solid var(--accent-orange);
}

.session-icon {
    font-size: 1.25rem;
}

.session-info {
    flex: 1;
}

.session-info h4 {
    font-size: 0.875rem;
    margin-bottom: 2px;
}

.session-info .muted {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.session-status {
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
}

.session-status.active {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
}

.session-status.completed {
    background: rgba(0, 212, 255, 0.15);
    color: var(--accent-cyan);
}

.session-status archived {
    background: rgba(107, 114, 128, 0.15);
    color: var(--text-muted);
}
</style>