/**
 * Admin JavaScript for AVIF Image Converter
 * Handles form submission and user confirmation for bulk image conversion
 * 
 * @since 1.0.0
 */

(($) => {

    // Ensure jQuery is available
    if (typeof $ === 'undefined') {
        console.error('jQuery is required for AVIF conversion functionality');
        return;
    }

    /**
     * Initialize admin functionality
     */
    const initAVIFConverter = () => {
        // Cache form selector for better performance
        const $forms = $('form');

        // Handle form submission
        $forms.on('submit', handleFormSubmit);
    };

    /**
     * Handle form submission and confirmation
     * @param {Event} e - Form submit event
     * @returns {boolean|void}
     */
    const handleFormSubmit = function(e) {
        try {
            const $form = $(this);
            const $convertInput = $form.find('input[name="convert_to_avif"]');

            if ($convertInput.length) {
                const confirmMessage = 'Are you sure you want to convert all images to AVIF? This process cannot be undone.';
                
                if (!window.confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }
        } catch (error) {
            console.error('Error handling AVIF conversion:', error);
            e.preventDefault();
            return false;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(initAVIFConverter);

})(jQuery);