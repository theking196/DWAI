@extends('layouts.app')

@section('title', 'Login - DWAI Studio')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>DWAI Studio</h1>
            <p>Private AI Development Environment</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="{{ old('email') }}"
                       required 
                       autofocus
                       autocomplete="email">
                @error('email')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       required
                       autocomplete="current-password">
                @error('password')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            <p class="muted">🔒 Private local environment</p>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark-bg);
}

.auth-card {
    width: 100%;
    max-width: 400px;
    padding: var(--space-xl);
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-lg);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--space-xl);
}

.auth-header h1 {
    font-size: 1.5rem;
    margin-bottom: var(--space-xs);
}

.auth-header p {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.auth-form .form-group {
    margin-bottom: var(--space-md);
}

.auth-form label {
    display: block;
    margin-bottom: var(--space-xs);
    font-size: 0.875rem;
    font-weight: 500;
}

.auth-form input[type="email"],
.auth-form input[type="password"] {
    width: 100%;
    padding: var(--space-sm) var(--space-md);
    background: var(--dark-surface);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    color: var(--text-primary);
}

.auth-form input:focus {
    outline: none;
    border-color: var(--accent-orange);
}

.auth-form .checkbox {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.auth-form .checkbox input {
    width: auto;
}

.auth-form .error {
    color: var(--error);
    font-size: 0.75rem;
    margin-top: var(--space-xs);
}

.auth-footer {
    text-align: center;
    margin-top: var(--space-lg);
    padding-top: var(--space-lg);
    border-top: 1px solid var(--panel-border);
}
</style>
@endsection
