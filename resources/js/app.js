/**
 * DWAI Studio - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('DWAI Studio loaded');
    
    // Simple form handling
    const forms = document.querySelectorAll('form[data-ajax]');
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const response = await fetch(form.action || form.method === 'POST' ? window.location.href : form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                }
            });
            if (response.ok) {
                window.location.reload();
            }
        });
    });
});
