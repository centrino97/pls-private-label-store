/**
 * Frontend custom order form JavaScript.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#pls-custom-order-form');
        const $messages = $('#pls-custom-order-messages');
        const $submit = $form.find('button[type="submit"]');

        $form.on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            $messages.removeClass('success error').hide().text('');

            // Disable submit button
            $submit.prop('disabled', true).text(PLS_CustomOrder.submitting || 'Submitting...');

            // Collect form data
            const formData = {
                action: 'pls_submit_custom_order',
                nonce: PLS_CustomOrder.nonce,
                contact_name: $('#pls-contact-name').val(),
                contact_email: $('#pls-contact-email').val(),
                contact_phone: $('#pls-contact-phone').val(),
                company_name: $('#pls-company-name').val(),
                category_id: $('#pls-product-category').val(),
                message: $('#pls-message').val(),
                quantity_needed: $('#pls-quantity-needed').val(),
                budget: $('#pls-budget').val(),
                timeline: $('#pls-timeline').val(),
            };

            // Submit via AJAX
            $.ajax({
                url: PLS_CustomOrder.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $messages.addClass('success').text(response.data.message).show();
                        $form[0].reset();
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $messages.offset().top - 100
                        }, 500);
                    } else {
                        $messages.addClass('error').text(response.data.message || 'An error occurred. Please try again.').show();
                    }
                },
                error: function() {
                    $messages.addClass('error').text('Network error. Please check your connection and try again.').show();
                },
                complete: function() {
                    $submit.prop('disabled', false).text('Submit Request');
                }
            });
        });
    });
})(jQuery);
