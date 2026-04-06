@extends('layouts.app')

@section('title', 'Login - DWAI Studio')

@section('content')
<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">🎬</div>
                <h1>DWAI Studio</h1>
                <p>AI Development Environment</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email') }}"
                           required 
                           autofocus
                           autocomplete="email"
                           placeholder="Enter your email">
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
                           autocomplete="current-password"
                           placeholder="Enter your password">
                    @error('password')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>🔒 Private local environment</p>
            </div>
        </div>
    </div>
</div>

<style>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0a0e17 0%, #0d1524 50%, #131d30 100%);
    padding: 20px;
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: #131d30;
    border: 1px solid #1e2d45;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.login-header {
    text-align: center;
    margin-bottom: 32px;
}

.logo {
    font-size: 48px;
    margin-bottom: 16px;
}

.login-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: #ff6b35;
}

.login-header p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.login-form .form-group {
    margin-bottom: 20px;
}

.login-form label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #94a3b8;
}

.login-form input[type="email"],
.login-form input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    background: #0a0e17;
    border: 1px solid #1e2d45;
    border-radius: 8px;
    color: #f0f4f8;
    font-size: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.login-form input::placeholder {
    color: #64748b;
}

.login-form input:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
}

.login-form .error {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    display: block;
}

.form-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #94a3b8;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    accent-color: #ff6b35;
}

.btn-block {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
}

.login-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #1e2d45;
}

.login-footer p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}
</style>
@endsection
