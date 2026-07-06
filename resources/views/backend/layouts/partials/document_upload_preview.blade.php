{{-- Include this partial after a file input that has class "document-upload-with-preview" and name="document" --}}
<div class="document-preview-container mt-2" style="display:none;">
    <p class="document-preview-filename text-muted small mb-1"></p>
    <img class="document-preview-image rounded border" style="max-width:100%; max-height:320px; display:none;" alt="@lang('messages.document_preview_alt')">
    <iframe class="document-preview-pdf rounded border" style="width:100%; height:360px; display:none;"></iframe>
</div>
<script>
(function() {
    var documentPreviewI18n = {
        label: @json(__('messages.document_preview_label'))
    };
    function initDocumentPreview() {
        document.querySelectorAll('input.document-upload-with-preview').forEach(function(input) {
            if (input._previewInitialized) return;
            input._previewInitialized = true;
            var container = (input.closest('.form-group') && input.closest('.form-group').querySelector('.document-preview-container')) || input.nextElementSibling;
            if (!container || !container.classList.contains('document-preview-container')) return;
            var img = container.querySelector('.document-preview-image');
            var iframe = container.querySelector('.document-preview-pdf');
            var filenameP = container.querySelector('.document-preview-filename');
            input.addEventListener('change', function() {
                var file = this.files && this.files[0];
                if (container._prevUrl) { URL.revokeObjectURL(container._prevUrl); container._prevUrl = null; }
                img.style.display = 'none';
                if (iframe) iframe.style.display = 'none';
                if (iframe && iframe.src) iframe.src = '';
                if (!file) {
                    container.style.display = 'none';
                    if (filenameP) filenameP.textContent = '';
                    return;
                }
                container.style.display = 'block';
                if (filenameP) filenameP.textContent = documentPreviewI18n.label + file.name;
                var url = URL.createObjectURL(file);
                container._prevUrl = url;
                if (file.type.indexOf('image/') === 0 && img) {
                    img.src = url;
                    img.style.display = 'block';
                } else if (file.type === 'application/pdf' && iframe) {
                    iframe.src = url;
                    iframe.style.display = 'block';
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocumentPreview);
    } else {
        initDocumentPreview();
    }
})();
</script>
