/**
 * Custom Orders Kanban board JavaScript.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const $board = $('#pls-custom-orders-kanban');
        const $columns = $board.find('.pls-kanban-column__cards');

        // Initialize drag and drop
        if ( typeof $.fn.sortable !== 'undefined' ) {
            $columns.sortable({
                connectWith: '.pls-kanban-column__cards',
                placeholder: 'pls-kanban-card-placeholder',
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.height());
                },
                update: function(e, ui) {
                    if ( ! ui.sender ) {
                        return; // Only handle drops, not drags within same column
                    }

                    const $card = ui.item;
                    const orderId = $card.data('order-id');
                    const newStage = $card.closest('.pls-kanban-column').data('stage');
                    const oldStage = $card.closest('.pls-kanban-column').data('stage-old') || $card.data('old-stage');

                    // Update status via AJAX
                    $.ajax({
                        url: PLS_CustomOrders.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'pls_update_custom_order_status',
                            nonce: PLS_CustomOrders.nonce,
                            order_id: orderId,
                            status: newStage
                        },
                        success: function(response) {
                            if ( ! response.success ) {
                                // Revert on error
                                $card.closest('.pls-kanban-column__cards').sortable('cancel');
                                alert( response.data.message || 'Failed to update status.' );
                            } else {
                                // Update counts
                                updateColumnCounts();
                            }
                        },
                        error: function() {
                            $card.closest('.pls-kanban-column__cards').sortable('cancel');
                            alert( 'Network error. Please try again.' );
                        }
                    });
                }
            });
        }

        // View order details
        $(document).on('click', '.pls-view-order', function(e) {
            e.preventDefault();
            const orderId = $(this).data('order-id');
            loadOrderDetails(orderId);
        });

        // Close modal
        $(document).on('click', '.pls-modal__close', function() {
            $('#pls-order-detail-modal').removeClass('is-active');
        });
        
        // Close modal on background click
        $(document).on('click', '#pls-order-detail-modal', function(e) {
            if ($(e.target).is('#pls-order-detail-modal')) {
                $(this).removeClass('is-active');
            }
        });

        // Save all order changes
        $(document).on('click', '#pls-save-order-all', function() {
            const orderId = $(this).data('order-id');
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: PLS_CustomOrders.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_update_custom_order',
                    nonce: PLS_CustomOrders.nonce,
                    order_id: orderId,
                    // Contact info
                    contact_name: $('#pls-edit-contact-name').val(),
                    contact_email: $('#pls-edit-contact-email').val(),
                    contact_phone: $('#pls-edit-contact-phone').val(),
                    company_name: $('#pls-edit-company-name').val(),
                    // Order details
                    category_id: $('#pls-edit-category').val(),
                    quantity_needed: $('#pls-edit-quantity').val(),
                    budget: $('#pls-edit-budget').val(),
                    timeline: $('#pls-edit-timeline').val(),
                    status: $('#pls-order-status').val(),
                    message: $('#pls-edit-message').val(),
                    // Financial info
                    production_cost: $('#pls-order-production-cost').val(),
                    total_value: $('#pls-order-total-value').val()
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#pls-order-detail-modal').hide();
                        location.reload();
                    } else {
                        alert( response.data.message || 'Failed to update.' );
                        $btn.prop('disabled', false).text('Save All Changes');
                    }
                },
                error: function() {
                    alert( 'Network error. Please try again.' );
                    $btn.prop('disabled', false).text('Save All Changes');
                }
            });
        });

        // Quick stage change buttons
        $(document).on('click', '.pls-stage-change', function() {
            const orderId = $(this).data('order-id');
            const newStage = $(this).data('stage');
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: PLS_CustomOrders.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_update_custom_order_status',
                    nonce: PLS_CustomOrders.nonce,
                    order_id: orderId,
                    status: newStage
                },
                success: function(response) {
                    if ( response.success ) {
                        // Reload the order details to show new stage
                        loadOrderDetails(orderId);
                        // Update the kanban board
                        updateColumnCounts();
                    } else {
                        alert( response.data.message || 'Failed to update status.' );
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert( 'Network error. Please try again.' );
                    $btn.prop('disabled', false);
                }
            });
        });

        // Save order financials (legacy - kept for compatibility)
        $(document).on('click', '#pls-save-order-financials', function() {
            const orderId = $(this).data('order-id');
            const productionCost = parseFloat($('#pls-order-production-cost').val()) || 0;
            const totalValue = parseFloat($('#pls-order-total-value').val()) || 0;
            const commissionRate = PLS_CustomOrders.commission_percent;
            const commissionAmount = totalValue > 0 ? (totalValue * commissionRate / 100) : 0;
            const commissionConfirmed = $('#pls-commission-confirmed').is(':checked') ? 1 : 0;

            $.ajax({
                url: PLS_CustomOrders.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_update_custom_order_financials',
                    nonce: PLS_CustomOrders.nonce,
                    order_id: orderId,
                    production_cost: productionCost,
                    total_value: totalValue,
                    nikola_commission_rate: commissionRate,
                    nikola_commission_amount: commissionAmount,
                    commission_confirmed: commissionConfirmed
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#pls-order-detail-modal').removeClass('is-active');
                        $('body').removeClass('pls-modal-open');
                        alert( 'Financials updated successfully.' );
                        location.reload();
                    } else {
                        alert( response.data.message || 'Failed to update.' );
                    }
                },
                error: function() {
                    alert( 'Network error. Please try again.' );
                }
            });
        });

        // Handle status change - show/hide commission confirmation checkbox
        $(document).on('change', '#pls-order-status', function() {
            const status = $(this).val();
            const $commissionRow = $(this).closest('tr').next('tr');
            
            if (status === 'done') {
                // Show commission confirmation checkbox if not already present
                if (!$commissionRow.find('#pls-commission-confirmed').length) {
                    const $newRow = $('<tr><th>Commission Confirmed</th><td><label><input type="checkbox" id="pls-commission-confirmed" value="1" /> Mark commission as paid (order is complete and payment received)</label></td></tr>');
                    $(this).closest('tr').after($newRow);
                }
            } else {
                // Hide commission confirmation checkbox
                if ($commissionRow.find('#pls-commission-confirmed').length) {
                    $commissionRow.remove();
                }
            }
        });

        // Mark as invoiced/paid
        $(document).on('click', '.pls-mark-invoiced, .pls-mark-paid', function() {
            const orderId = $(this).data('order-id');
            const action = $(this).hasClass('pls-mark-invoiced') ? 'pls_mark_custom_order_invoiced' : 'pls_mark_custom_order_paid';

            $.ajax({
                url: PLS_CustomOrders.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: PLS_CustomOrders.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#pls-order-detail-modal').removeClass('is-active');
                        $('body').removeClass('pls-modal-open');
                        loadOrderDetails(orderId);
                    } else {
                        alert( response.data.message || 'Failed to update.' );
                    }
                },
                error: function() {
                    alert( 'Network error. Please try again.' );
                }
            });
        });

        function loadOrderDetails(orderId) {
            $.ajax({
                url: PLS_CustomOrders.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_get_custom_order_details',
                    nonce: PLS_CustomOrders.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#pls-order-detail-content').html(response.data.html);
                        $('#pls-order-detail-modal').show();
                    } else {
                        alert( response.data.message || 'Failed to load order details.' );
                    }
                },
                error: function() {
                    alert( 'Network error. Please try again.' );
                }
            });
        }

        function updateColumnCounts() {
            $('.pls-kanban-column').each(function() {
                const count = $(this).find('.pls-kanban-card').length;
                $(this).find('.pls-kanban-count').text(count);
            });
        }
    });
})(jQuery);
