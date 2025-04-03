// Update your existing admin.js

jQuery(document).ready(function($) {
    // Handle demo button click
    $(document).on('click', '.demo-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var demoUrl = $(this).data('demo-url');
        $('#template-preview-frame').attr('src', demoUrl);
        $('#template-preview-modal').show();
    });

    // Handle responsive preview buttons
    $('.responsive-buttons button').click(function() {
        var device = $(this).data('device');
        
        // Update active button
        $('.responsive-buttons button').removeClass('active');
        $(this).addClass('active');
        
        // Update iframe class
        const frame = $('#template-preview-frame');
        frame.removeClass('desktop tablet mobile');
        frame.addClass(device);
    });

    // Handle back to library button
    $('.back-to-library').click(function() {
        closeModal();
    });

    // Handle modal close button
    $('.close-modal').click(function() {
        closeModal();
    });

    // Handle apply kit button
    $('.apply-kit').click(function() {
        $('#apply-kit-modal').addClass('is-open');
    });

    // Handle click outside modal
    $(window).click(function(e) {
        if ($(e.target).is('#template-preview-modal')) {
            closeModal();
        }
    });

    // Close modal function
    function closeModal() {
        $('#template-preview-modal').hide();
        $('#template-preview-frame').attr('src', '');
        // Reset responsive view to desktop
        $('.responsive-buttons button').removeClass('active');
        $('.view-desktop').addClass('active');
        $('#template-preview-frame').removeClass('desktop tablet mobile');
    }

    // Handle ESC key to close modal
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            closeModal();
        }
    });


    
});