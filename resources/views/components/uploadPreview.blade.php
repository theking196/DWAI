<!-- Upload with Preview Component -->
<form id="{{ $id ?? 'upload-form' }}" 
      action="{{ route($action) }}" 
      method="POST" 
      enctype="multipart/form-data"
      class="upload-with-preview">
    @csrf
    
    @if(isset($project_id))
    <input type="hidden" name="project_id" value="{{ $project_id }}">
    @endif
    
    @if(isset($session_id))
    <input type="hidden" name="session_id" value="{{ $session_id }}">
    @endif
    
    <!-- Preview Area -->
    @if(isset($currentImage) && $currentImage)
    <div class="current-image-preview">
        <img src="{{ $currentImage }}" alt="Current image">
        <button type="button" class="btn-remove" onclick="removeImage(this)" title="Remove">&times;</button>
    </div>
    @else
    <div class="image-drop-zone" id="drop-zone-{{ $id ?? 'upload' }}">
        <input type="file" 
               name="image" 
               id="file-input-{{ $id ?? 'upload' }}"
               accept="image/*"
               onchange="previewImage(this)">
        <div class="drop-zone-content">
            <span class="drop-icon">📷</span>
            <p>Click or drag image here</p>
            <p class="muted">JPG, PNG, GIF (max 10MB)</p>
        </div>
    </div>
    @endif
    
    <!-- Hidden file input for replacement -->
    <input type="file" 
           name="replace_image" 
           id="replace-input-{{ $id ?? 'upload' }}"
           accept="image/*"
           style="display:none"
           onchange="replaceImage(this)">
    
    <button type="button" class="btn btn-secondary btn-sm" onclick="replaceImageButton()">
        {{ isset($currentImage) ? 'Replace' : 'Upload' }}
    </button>
</form>

<style>
.upload-with-preview {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.current-image-preview {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--dark-surface);
}

.current-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.current-image-preview .btn-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    background: rgba(239, 68, 68, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-drop-zone {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border: 2px dashed var(--panel-border);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 150ms, background 150ms;
}

.image-drop-zone:hover {
    border-color: var(--accent-orange);
    background: rgba(255, 107, 53, 0.05);
}

.image-drop-zone input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.drop-zone-content {
    text-align: center;
    color: var(--text-muted);
}

.drop-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: var(--space-sm);
}

/* Preview state */
.image-drop-zone.has-preview {
    border-style: solid;
    border-color: var(--success);
}

.image-drop-zone.has-preview .drop-zone-content {
    display: none;
}
</style>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var dropZone = input.closest('.image-drop-zone');
            if (!dropZone) {
                // Create preview for replacement
                dropZone = document.createElement('div');
                dropZone.className = 'current-image-preview';
                dropZone.innerHTML = '<img src="' + e.target.result + '">';
                dropZone.innerHTML += '<button type="button" class="btn-remove" onclick="removeImage(this)">&times;</button>';
                input.closest('.upload-with-preview').insertBefore(dropZone, input);
                input.style.display = 'none';
            } else {
                dropZone.classList.add('has-preview');
                if (!dropZone.querySelector('img')) {
                    dropZone.innerHTML = '<img src="' + e.target.result + '">';
                }
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function replaceImageButton() {
    document.getElementById('replace-input-{{ $id ?? "upload" }}').click();
}

function replaceImage(input) {
    if (input.files && input.files[0]) {
        previewImage(input);
    }
}

function removeImage(btn) {
    var preview = btn.closest('.current-image-preview');
    var form = btn.closest('.upload-with-preview');
    var originalInput = form.querySelector('input[name="image"]');
    var replaceInput = form.querySelector('input[name="replace_image"]');
    
    if (replaceInput) replaceInput.value = '';
    if (originalInput) originalInput.value = '';
    
    // Remove preview and show drop zone
    if (preview) {
        preview.remove();
    }
    
    var dropZone = form.querySelector('.image-drop-zone');
    if (dropZone) {
        dropZone.classList.remove('has-preview');
    }
}
</script>