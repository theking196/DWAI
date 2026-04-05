<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DWAI Studio - Cinematic Intelligence Engine">
    <meta name="theme-color" content="#0a0e17">
    <title>@yield('title', 'DWAI Studio')</title>
    
    {{-- Vite Asset Loading --}}
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="app-container">
        {{-- Header --}}
        <header class="app-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="sidebar">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </button>
                
                <a href="/" class="logo">
                    <span class="logo-icon">🎬</span>
                    <span class="logo-text">DWAI Studio</span>
                </a>
                
                <nav class="header-breadcrumb" aria-label="Breadcrumb">
                    @yield('breadcrumb', '')
                </nav>
            </div>
            
            <div class="header-center">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="Search projects..." aria-label="Search">
                </div>
            </div>
            
            <div class="header-right">
                @include('partials.theme-toggle')
                
                <button class="header-btn" title="Notifications" aria-label="Notifications">🔔</button>
                <button class="header-btn" title="Settings" aria-label="Settings">⚙️</button>
                <div class="user-avatar" tabindex="0" role="button" aria-label="User menu">U</div>
            </div>
        </header>
        
        {{-- Sidebar Overlay (mobile) --}}
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        {{-- Sidebar --}}
        <aside class="sidebar closed" id="sidebar" role="complementary" aria-label="Main navigation">
            <nav class="sidebar-nav" role="menu" aria-label="Primary">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <span class="nav-icon">📁</span>
                    <span class="nav-label">Projects</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">💭</span>
                    <span class="nav-label">Sessions</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">🧠</span>
                    <span class="nav-label">Memory</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">🔗</span>
                    <span class="nav-label">Connections</span>
                </a>
                
                <div class="nav-divider"></div>
                
                <a href="#" class="nav-item">
                    <span class="nav-icon">📅</span>
                    <span class="nav-label">Timeline</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📚</span>
                    <span class="nav-label">Canon</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">🖼️</span>
                    <span class="nav-label">References</span>
                </a>
                
                <div class="nav-divider"></div>
                
                <a href="#" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p class="muted">DWAI Studio v1.0</p>
            </div>
        </aside>
        
        {{-- Main Content --}}
        <main class="app-main" role="main">
            @yield('content')
        </main>
        
        {{-- Footer --}}
        <footer class="app-footer">
            <p>&copy; 2026 DWAI Studio. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Help</a>
            </div>
        </footer>
    </div>
    
    {{-- Vite JS --}}
    @vite(['resources/js/app.js'])
</body>
</html>