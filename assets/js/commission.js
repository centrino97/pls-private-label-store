/**
 * Commission page JavaScript.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Send monthly report button
        $('#pls-send-monthly-report').on('click', function() {
            $('#pls-send-report-modal').addClass('is-active');
        });

        // Close modal
        $('#pls-send-report-modal .pls-modal__close, #pls-cancel-report').on('click', function() {
            $('#pls-send-report-modal').removeClass('is-active');
        });

        // Send report form
        $('#pls-send-report-form').on('submit', function(e) {
            e.preventDefault();
            const month = $('#pls-report-month').val();
            
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_send_monthly_report',
                    nonce: PLS_Commission.nonce,
                    month: month
                },
                success: function(response) {
                    if (response.success) {
                        alert('Monthly report sent successfully!');
                        $('#pls-send-report-modal').removeClass('is-active');
                        location.reload();
                    } else {
                        alert('Failed to send report: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Failed to send report. Please try again.');
                }
            });
        });

        // Mark as invoiced (item)
        $('.pls-mark-invoiced-item').on('click', function() {
            const $btn = $(this);
            const id = $btn.data('id');
            const type = $btn.data('type');
            
            $btn.prop('disabled', true).text('Updating...');
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_mark_commission_invoiced',
                    nonce: PLS_Commission.nonce,
                    id: id,
                    type: type
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark as invoiced: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $btn.prop('disabled', false).text('Mark Invoiced');
                    }
                },
                error: function() {
                    alert('Network error while marking as invoiced. Please try again.');
                    $btn.prop('disabled', false).text('Mark Invoiced');
                }
            });
        });

        // Mark as paid (item)
        $('.pls-mark-paid-item').on('click', function() {
            const $btn = $(this);
            const id = $btn.data('id');
            const type = $btn.data('type');
            
            if (!confirm('Mark this commission as paid?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Updating...');
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_mark_commission_paid',
                    nonce: PLS_Commission.nonce,
                    id: id,
                    type: type
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark as paid: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $btn.prop('disabled', false).text('Mark Paid');
                    }
                },
                error: function() {
                    alert('Network error while marking as paid. Please try again.');
                    $btn.prop('disabled', false).text('Mark Paid');
                }
            });
        });

        // Mark as invoiced (monthly)
        $('.pls-mark-invoiced-monthly').on('click', function() {
            const $btn = $(this);
            const month = $btn.data('month');
            
            $btn.prop('disabled', true).text('Updating...');
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_mark_commission_invoiced_monthly',
                    nonce: PLS_Commission.nonce,
                    month: month
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark month as invoiced: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $btn.prop('disabled', false).text('Mark All Invoiced');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Mark All Invoiced');
                }
            });
        });

        // Mark as paid (monthly)
        $('.pls-mark-paid-monthly').on('click', function() {
            const $btn = $(this);
            const month = $btn.data('month');
            
            if (!confirm('Mark all commissions for this month as paid?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Updating...');
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_mark_commission_paid_monthly',
                    nonce: PLS_Commission.nonce,
                    month: month
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark month as paid: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $btn.prop('disabled', false).text('Mark All Paid');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Mark All Paid');
                }
            });
        });

        // Select all checkbox
        $('#pls-select-all-commissions').on('change', function() {
            $('.pls-commission-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Bulk action
        $('#pls-apply-bulk-action').on('click', function() {
            const $btn = $(this);
            const action = $('#pls-commission-bulk-action').val();
            const checked = $('.pls-commission-checkbox:checked');
            
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            
            if (checked.length === 0) {
                alert('Please select at least one commission.');
                return;
            }
            
            const ids = checked.map(function() {
                return $(this).val();
            }).get();
            
            if (action === 'mark_paid' && !confirm('Mark selected commissions as paid?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Processing...');
            $.ajax({
                url: PLS_Commission.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_bulk_update_commission',
                    nonce: PLS_Commission.nonce,
                    ids: ids,
                    action_type: action
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Bulk update failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $btn.prop('disabled', false).text('Apply');
                    }
                },
                error: function() {
                    alert('Network error during bulk update. Please try again.');
                    $btn.prop('disabled', false).text('Apply');
                }
            });
        });
    });
})(jQuery);
