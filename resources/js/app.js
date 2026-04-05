/**
 * DWAI Studio - Main JavaScript
 * Imports existing DWAI functionality
 */

// Import existing modules (these would be in node_modules after npm install)
import DWAI from '/dwai-studio/assets/js/app.js';
import models from '/dwai-studio/assets/js/models.js';
import upload from '/dwai-studio/assets/js/components/upload.js';
import collapsible from '/dwai-studio/assets/js/collapsible.js';

// Make available globally
window.DWAI = DWAI;
window.DWAI.models = models;
window.DWAI.upload = upload;

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DWAI] Laravel app initialized');
    
    // Initialize collapsible panels
    if (typeof collapsible.initCollapsiblePanels === 'function') {
        collapsible.initCollapsiblePanels();
    }
    
    // Initialize theme toggle
    initThemeToggle();
    
    // Initialize sidebar toggle
    initSidebar();
});

function initThemeToggle() {
    var themeBtns = document.querySelectorAll('.theme-btn');
    var savedTheme = localStorage.getItem('dwai_theme') || 'dark';
    
    themeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var theme = this.dataset.theme;
            document.documentElement.setAttribute('data-theme', theme);
            themeBtns.forEach(function(b) {
                b.classList.toggle('active', b.dataset.theme === theme);
            });
            localStorage.setItem('dwai_theme', theme);
        });
    });
}

function initSidebar() {
    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    
    if (!menuToggle || !sidebar) return;
    
    menuToggle.addEventListener('click', function() {
        var isOpen = sidebar.classList.toggle('open');
        sidebar.classList.toggle('closed', !isOpen);
        overlay.classList.toggle('visible', isOpen);
        menuToggle.setAttribute('aria-expanded', isOpen);
    });
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebar.classList.add('closed');
            overlay.classList.remove('visible');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    }
}

// Export for use in Laravel components
export default {
    init: function() {
        console.log('[DWAI] Initialized');
    }
};