/**
 * GFFM Public-Facing JavaScript
 *
 * @since 3.0.0
 */
(function($) {
    'use strict';

    $(function() {

        // --- Vendor Dashboard Accordion ---
        $('.gffm-accordion-header').on('click', function() {
            // Close other items
            $(this).closest('.gffm-accordion').find('.gffm-accordion-item').not($(this).parent()).removeClass('active');
            $(this).closest('.gffm-accordion').find('.gffm-accordion-content').not($(this).next()).slideUp();

            // Toggle current item
            $(this).parent().toggleClass('active');
            $(this).next('.gffm-accordion-content').slideToggle();
        });

        // --- Vendor Dashboard Form Submission (AJAX) ---
        $('#gffm-vendor-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var formData = new FormData(form[0]);
            var responseDiv = $('#gffm-ajax-response');
            var submitButton = form.find('button[type="submit"]');
            var originalButtonText = submitButton.text();

            submitButton.text('Saving...').prop('disabled', true);
            responseDiv.hide();

            $.ajax({
                url: gffm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        responseDiv.removeClass('gffm-error').addClass('gffm-success').text(response.data.message).slideDown();
                    } else {
                        responseDiv.removeClass('gffm-success').addClass('gffm-error').text(response.data.message).slideDown();
                    }
                },
                error: function() {
                    responseDiv.removeClass('gffm-success').addClass('gffm-error').text('An unknown error occurred.').slideDown();
                },
                complete: function() {
                    submitButton.text(originalButtonText).prop('disabled', false);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        });

        // --- Vendor Directory Filtering (AJAX) ---
        $('#gffm-directory-filters select, #gffm-directory-filters input[type="checkbox"]').on('change', function() {
            var filters = $('#gffm-directory-filters');
            var grid = $('#gffm-directory-grid');
            var loader = $('#gffm-directory-grid-loader');

            var data = {
                action: 'gffm_filter_directory',
                nonce: gffm_ajax.nonce,
                product_type: filters.find('#gffm-filter-product').val(),
                vendor_type: filters.find('#gffm-filter-vendor-type').val(),
                cdphp: filters.find('#gffm-filter-cdphp').is(':checked') ? 'yes' : ''
            };

            $.ajax({
                url: gffm_ajax.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    grid.css('opacity', 0.5);
                    loader.show();
                },
                success: function(response) {
                    grid.html(response);
                },
                error: function() {
                    grid.html('<p class="gffm-no-results">An error occurred while filtering.</p>');
                },
                complete: function() {
                    grid.css('opacity', 1);
                    loader.hide();
                }
            });
        });

    });

})(jQuery);
