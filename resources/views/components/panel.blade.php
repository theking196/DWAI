<!-- Panel Component -->
<div class="workspace-panel {{ $attributes->get('class', '') }}" {{ $attributes->only(['id', 'data-id']) }}>
    @if($title || $icon || $hasHeader ?? true)
    <div class="panel-header">
        <div class="panel-title">
            @if($icon)<span class="panel-icon">{!! $icon !!}</span>@endif
            @if($title)<h3>{!! $title !!}</h3>@endif
        </div>
        {{ $header ?? '' }}
    </div>
    @endif
    
    <div class="panel-body {{ $bodyClass ?? '' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer) && $footer)
    <div class="panel-footer">
        {{ $footer }}
    </div>
    @endif
</div>

<style>
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

.panel-title {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.panel-title h3 {
    font-size: 0.9375rem;
    font-weight: 600;
}

.panel-icon {
    font-size: 1.125rem;
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

.panel-footer {
    padding: var(--space-md) var(--space-lg);
    border-top: 1px solid var(--panel-border);
    background: var(--dark-surface);
}
</style>