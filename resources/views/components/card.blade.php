<!-- Card Component -->
<div class="card {{ $attributes->get('class', '') }}" {{ $attributes->only(['id', 'data-id']) }}>
    @if($title || isset($header))
    <div class="card-header">
        @if($icon)<span class="card-icon">{!! $icon !!}</span>@endif
        @if($title)<h3>{!! $title !!}</h3>@endif
        {{ $header ?? '' }}
    </div>
    @endif
    
    <div class="card-body {{ $bodyClass ?? '' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
    <div class="card-footer">
        {{ $footer }}
    </div>
    @endif
</div>

<style>
.card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: transform 150ms, box-shadow 150ms;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.card.clickable {
    cursor: pointer;
}

.card-header {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md);
    border-bottom: 1px solid var(--panel-border);
}

.card-header h3 {
    font-size: 0.9375rem;
    font-weight: 600;
}

.card-icon {
    font-size: 1.125rem;
}

.card-body {
    padding: var(--space-md);
}

.card-footer {
    padding: var(--space-md);
    border-top: 1px solid var(--panel-border);
    background: var(--dark-surface);
}
</style>