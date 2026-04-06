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
// AI Generator Form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ai-generate-form');
    const statusEl = document.getElementById('gen-status');
    const outputsEl = document.getElementById('ai-outputs');
    const noOutputsEl = document.getElementById('no-outputs');
    const generateBtn = document.getElementById('generate-btn');
    
    if (!form) return;
    
    // Generation type tabs
    document.querySelectorAll('.gen-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.gen-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const typeInput = form.querySelector('input[name="type"]');
            typeInput.value = this.dataset.type;
            
            const modelSelect = form.querySelector('select[name="model"]');
            if (this.dataset.type === 'image') {
                modelSelect.innerHTML = '<option value="dall-e-3">DALL-E 3</option><option value="dall-e-2">DALL-E 2</option>';
            } else {
                modelSelect.innerHTML = '<option value="gpt-4">GPT-4</option><option value="gpt-3.5">GPT-3.5</option>';
            }
        });
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const prompt = form.querySelector('textarea[name="prompt"]').value.trim();
        if (!prompt) return;
        
        // Show loading state
        generateBtn.disabled = true;
        generateBtn.textContent = '⏳ Generating...';
        statusEl.style.display = 'flex';
        
        if (noOutputsEl) noOutputsEl.style.display = 'none';
        
        const type = form.querySelector('input[name="type"]').value;
        const endpoint = type === 'image' ? '/ai/generate/image' : '/ai/generate/text';
        
        try {
            const formData = new FormData(form);
            formData.append('async', 'true');
            
            if (type === 'image' && window.projectId) {
            try {
                const refRes = await fetch(`/projects/${window.projectId}/references`);
                const refData = await refRes.json();
                if (refData.success && refData.references) {
                    formData.append('references', JSON.stringify(refData.references.map(r => r.path)));
            } catch(e) { console.log('No refs', e); }
            }
        }
        const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add pending output card
                addOutputCard({
                    id: Date.now(),
                    type: type,
                    model: form.querySelector('select[name="model"]').value,
                    status: 'pending',
                    result: '',
                    created_at: new Date().toISOString()
                });
                
                // Poll for status
                pollOutputStatus(type);
            } else {
                alert(data.error || 'Generation failed');
            }
        } catch (err) {
            console.error('Generation error:', err);
            alert('Failed to generate. Please try again.');
        } finally {
            generateBtn.disabled = false;
            generateBtn.textContent = '⚡ Generate';
            statusEl.style.display = 'none';
            form.querySelector('textarea[name="prompt"]').value = '';
        }
    });
});

function addOutputCard(output) {
    const outputsEl = document.getElementById('ai-outputs');
    const noOutputsEl = document.getElementById('no-outputs');
    
    if (noOutputsEl) noOutputsEl.style.display = 'none';
    
    const card = document.createElement('div');
    card.className = 'output-card';
    card.dataset.id = output.id;
    card.dataset.status = output.status;
    
    card.innerHTML = `
        <div class="output-header">
            <span class="output-type">${output.type === 'image' ? '🎨' : '💭'}</span>
            <span class="output-model">${output.model}</span>
            <span class="output-status ${output.status}">${output.status}</span>
        </div>
        <div class="output-content">
            ${output.status === 'pending' ? '<p class="muted">Waiting in queue...</p>' : ''}
            ${output.status === 'processing' ? '<p class="muted">Generating...</p>' : ''}
            ${output.type === 'image' && output.result ? `<img src="${output.result}" alt="AI Generated">` : ''}
            ${output.type === 'text' && output.result ? `<p>${output.result}</p>` : ''}
        </div>
        <div class="output-meta">
            <span>${new Date(output.created_at).toLocaleTimeString()}</span>
        </div>
    `;
    
    outputsEl.insertBefore(card, outputsEl.firstChild);
}

async function pollOutputStatus(type) {
    const checkInterval = 3000; // 3 seconds
    const maxChecks = 20; // 60 seconds max
    
    let checks = 0;
    
    const interval = setInterval(async () => {
        checks++;
        
        try {
            const response = await fetch(`/ai/outputs/${window.sessionId}/status/${window.latestOutputId || 0}`);
            const data = await response.json();
            
            if (data.status === 'completed') {
                clearInterval(interval);
                updateOutputCard(window.latestOutputId, {
                    status: 'completed',
                    result: data.result
                });
            } else if (data.status === 'failed') {
                clearInterval(interval);
                updateOutputCard(window.latestOutputId, {
                    status: 'failed',
                    error: data.error
                });
            }
        } catch (err) {
            console.error('Status poll error:', err);
        }
        
        if (checks >= maxChecks) {
            clearInterval(interval);
        }
    }, checkInterval);
}

function updateOutputCard(id, data) {
    const card = document.querySelector(`.output-card[data-id="${id}"]`);
    if (!card) return;
    
    card.dataset.status = data.status;
    const statusEl = card.querySelector('.output-status');
    statusEl.className = `output-status ${data.status}`;
    statusEl.textContent = data.status;
    
    const contentEl = card.querySelector('.output-content');
    if (data.status === 'completed') {
        contentEl.innerHTML = `<p>${data.result}</p>`;
    } else if (data.status === 'failed') {
        contentEl.innerHTML = `<p class="text-error">${data.error || 'Generation failed'}</p>`;
    }
}
