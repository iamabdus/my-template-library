<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Template Library', 'my-template-library'); ?></h1>
    <div class="mtl-templates-grid">
        <?php foreach ($this->templates as $template): ?>
            <div class="template-card">
                <img src="<?php echo esc_url($template['image']); ?>" alt="<?php echo esc_attr($template['title']); ?>">
                <div class="template-card-content">
                    <h3><?php echo esc_html($template['title']); ?></h3>
                </div>
                <button type="button" class="demo-button" 
                    data-template-id="<?php echo esc_attr($template['id']); ?>" 
                    data-demo-url="<?php echo esc_url($template['demo_url']); ?>"
                    data-kit-url="<?php echo esc_url($template['kit_url']); ?>">
                    <?php echo esc_html__('View Demo', 'my-template-library'); ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Template Preview Modal -->
    <div id="template-preview-modal" class="template-preview-modal">
        <div class="modal-toolbar">
            <div class="toolbar-left">
                <button class="back-to-library">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Back to Library
                </button>
            </div>
            <div class="toolbar-center">
                <div class="responsive-buttons">
                    <button class="view-desktop active" data-device="desktop">
                        <span class="dashicons dashicons-desktop"></span>
                    </button>
                    <button class="view-tablet" data-device="tablet">
                        <span class="dashicons dashicons-tablet"></span>
                    </button>
                    <button class="view-mobile" data-device="mobile">
                        <span class="dashicons dashicons-smartphone"></span>
                    </button>
                </div>
            </div>
            <div class="toolbar-right">
                <button type="button" class="apply-kit">Apply Kit</button>
                <button class="close-modal">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>
        <div class="modal-content" id="template-preview">
            <iframe id="template-preview-frame" class="preview-frame" src="" frameborder="0"></iframe>
        </div>
    </div>

    <?php require_once MTL_PLUGIN_DIR . 'includes/apply-kit-modal.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let previewedTemplateId = null;
    let previewedKitUrl = null;
    const previewModal = document.getElementById('template-preview-modal');
    const applyKitModal = document.getElementById('apply-kit-modal');
    const previewFrame = document.getElementById('template-preview-frame');

    // View Demo Button Handler
    document.querySelectorAll('.demo-button').forEach(button => {
        button.addEventListener('click', function() {
            previewedTemplateId = this.getAttribute('data-template-id');
            previewedKitUrl = this.getAttribute('data-kit-url');
            const demoUrl = this.getAttribute('data-demo-url');

            // Store template data in sessionStorage
            sessionStorage.setItem('selectedTemplateId', previewedTemplateId);
            sessionStorage.setItem('selectedKitUrl', previewedKitUrl);

            previewFrame.src = demoUrl;
            previewModal.classList.add('is-open');
            previewModal.style.display = 'block';
        });
    });

    // Close Preview Modal
    document.querySelector('.close-modal').addEventListener('click', function() {
        previewModal.classList.remove('is-open');
        previewModal.style.display = 'none';
        previewFrame.src = '';
    });

    // Back to Library Button
    document.querySelector('.back-to-library').addEventListener('click', function() {
        previewModal.classList.remove('is-open');
        previewModal.style.display = 'none';
        previewFrame.src = '';
    });

    // When Apply Kit is clicked, open the Apply Kit modal with stored data
    document.querySelector('.apply-kit').addEventListener('click', function() {
        if (previewedTemplateId && previewedKitUrl) {
            // Close the preview modal
            previewModal.classList.remove('is-open');
            previewModal.style.display = 'none';

            // Open the Apply Kit modal
            applyKitModal.classList.add('is-open');
            applyKitModal.style.display = 'block';

            // Trigger custom event to notify the Apply Kit modal
            const event = new CustomEvent('templateSelected', {
                detail: {
                    templateId: previewedTemplateId,
                    kitUrl: previewedKitUrl
                }
            });
            document.dispatchEvent(event);
        }
    });
});
</script>