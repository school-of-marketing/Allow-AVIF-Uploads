// admin.js
jQuery(document).ready(function ($) {
    // Add any interactive functionality here
    // Example: Confirm before bulk conversion
    $('form').on('submit', function (e) {
        if ($(this).find('input[name="convert_to_avif"]').length) {
            if (!confirm('Are you sure you want to convert all images to AVIF? This process cannot be undone.')) {
                e.preventDefault();
            }
        }
    });
});