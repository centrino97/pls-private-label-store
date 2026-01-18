/**
 * Onboarding system JavaScript.
 */

(function($) {
    'use strict';

    let onboardingData = null;
    let currentPageSteps = [];
    let currentStepIndex = 0;

    $(document).ready(function() {
        if ( ! PLS_Onboarding || ! PLS_Onboarding.steps ) {
            return;
        }

        onboardingData = PLS_Onboarding;
        const currentPage = onboardingData.current_page || 'dashboard';
        
        // Get steps for current page
        if ( onboardingData.steps[ currentPage ] ) {
            currentPageSteps = onboardingData.steps[ currentPage ].steps || [];
        }

        // Start tutorial button handler
        $('#pls-start-tutorial').on('click', function() {
            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_start_onboarding',
                    nonce: PLS_Onboarding.nonce
                },
                success: function() {
                    location.reload();
                }
            });
        });

        // Inject help buttons into modals
        injectModalHelpButtons();

        // Show onboarding card if active (only when not in modal)
        function checkAndShowOnboarding() {
            if ( onboardingData.is_active && !$('.pls-modal.is-active').length ) {
                if (!$('#pls-onboarding-card').length) {
                    initOnboardingCard();
                }
                $('#pls-onboarding-card').show();
            } else {
                $('#pls-onboarding-card').hide();
            }
        }

        checkAndShowOnboarding();

        // Show Help button in page header if onboarding not active
        if ( !onboardingData.is_active ) {
            initHelpButton();
        }

        // Watch for modal opens/closes
        $(document).on('click', '.pls-modal__close, .pls-modal-cancel, [data-pls-open-modal]', function() {
            setTimeout(function() {
                injectModalHelpButtons();
                checkAndShowOnboarding();
            }, 300);
        });

        // Watch for modal class changes using MutationObserver
        if (typeof MutationObserver !== 'undefined') {
            const modalObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const $target = $(mutation.target);
                        if ($target.hasClass('pls-modal')) {
                            setTimeout(function() {
                                injectModalHelpButtons();
                                checkAndShowOnboarding();
                            }, 100);
                        }
                    }
                });
            });

            // Observe all existing modals
            $('.pls-modal').each(function() {
                modalObserver.observe(this, { attributes: true, attributeFilter: ['class'] });
            });

            // Observe body for new modals
            const bodyObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && $(node).hasClass('pls-modal')) {
                            modalObserver.observe(node, { attributes: true, attributeFilter: ['class'] });
                            setTimeout(injectModalHelpButtons, 100);
                        }
                    });
                });
            });

            bodyObserver.observe(document.body, { childList: true, subtree: true });
        }
    });

    function injectModalHelpButtons() {
        ensureModalHelpButtons();

        // Attach tooltip handlers (only once)
        if (!$(document).data('pls-help-handlers-attached')) {
            $(document).on('click', '.pls-help-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const section = $btn.data('section');
                const page = $btn.data('page');
                
                // Get helper content from server
                showHelpTooltip($btn, section, page);
            });
            $(document).data('pls-help-handlers-attached', true);
        }
    }

    function showHelpTooltip($button, section, page) {
        // Remove existing tooltip
        $('.pls-help-tooltip').remove();

        // Get helper content (simplified - in production, fetch via AJAX)
        const content = getHelperContentForSection(page, section);
        if (!content) return;

        const $tooltip = $('<div class="pls-help-tooltip"></div>');
        $tooltip.append('<h4>' + (content.title || 'Help') + '</h4>');
        
        if (content.tips && content.tips.length) {
            const $tipsList = $('<ul class="pls-help-tips"></ul>');
            content.tips.forEach(function(tip) {
                $tipsList.append('<li>' + tip + '</li>');
            });
            $tooltip.append($tipsList);
        }

        if (content.validation && content.validation.length) {
            const $validationList = $('<ul class="pls-help-validation"></ul>');
            content.validation.forEach(function(rule) {
                $validationList.append('<li>' + rule + '</li>');
            });
            $tooltip.append('<h5>Requirements:</h5>');
            $tooltip.append($validationList);
        }

        $('body').append($tooltip);

        // Position tooltip near button
        const btnOffset = $button.offset();
        const btnWidth = $button.outerWidth();
        const btnHeight = $button.outerHeight();
        const tooltipWidth = $tooltip.outerWidth();
        const tooltipHeight = $tooltip.outerHeight();

        let left = btnOffset.left + btnWidth + 10;
        let top = btnOffset.top;

        // Adjust if tooltip goes off screen
        if (left + tooltipWidth > $(window).width()) {
            left = btnOffset.left - tooltipWidth - 10;
        }
        if (top + tooltipHeight > $(window).height()) {
            top = $(window).height() - tooltipHeight - 10;
        }

        $tooltip.css({ left: left + 'px', top: top + 'px' }).fadeIn(200);

        // Close on click outside or Escape key
        $(document).on('click.helpTooltip', function(e) {
            if (!$(e.target).closest('.pls-help-btn, .pls-help-tooltip').length) {
                $tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
                $(document).off('click.helpTooltip keydown.helpTooltip');
            }
        });

        $(document).on('keydown.helpTooltip', function(e) {
            if (e.key === 'Escape') {
                $tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
                $(document).off('click.helpTooltip keydown.helpTooltip');
            }
        });
    }

    function getHelperContentForSection(page, section) {
        // Fetch helper content from server (consolidated source)
        let content = null;
        
        $.ajax({
            url: PLS_Onboarding.ajax_url,
            type: 'POST',
            async: false, // Synchronous for immediate use
            data: {
                action: 'pls_get_helper_content',
                nonce: PLS_Onboarding.nonce,
                page: page,
                section: section
            },
            success: function(resp) {
                if (resp && resp.success && resp.data && resp.data.content) {
                    if (section) {
                        content = resp.data.content[section] || null;
                    } else {
                        content = resp.data.content;
                    }
                }
            },
            error: function() {
                // Fallback to hardcoded content if AJAX fails
                content = getHelperContentFallback(page, section);
            }
        });
        
        return content || getHelperContentFallback(page, section);
    }

    function getHelperContentFallback(page, section) {
        // Fallback helper content (matches PHP get_helper_content)
        const contentMap = {
            'products': {
                'general': {
                    title: 'General Information',
                    tips: [
                        'Product name will be used as the WooCommerce product title.',
                        'Select categories to organize products in your store.',
                        'Upload featured image and gallery images for product display.'
                    ],
                    validation: [
                        'Product name is required.',
                        'At least one category must be selected.'
                    ]
                },
                'packs': {
                    title: 'Pack Tiers',
                    tips: [
                        'Pack tiers define different quantities and pricing options.',
                        'Each tier will become a WooCommerce product variation.',
                        'Enable tiers that should be available for purchase.'
                    ],
                    validation: [
                        'At least one pack tier must be enabled.',
                        'Units and price must be greater than 0.'
                    ]
                },
                'attributes': {
                    title: 'Product Options',
                    tips: [
                        'Product options allow customers to customize their order.',
                        'Set tier-based pricing rules for each option value.',
                        'Options will sync to WooCommerce as product attributes.'
                    ]
                }
            },
            'bundles': {
                'create': {
                    title: 'Create Bundle',
                    tips: [
                        'Bundles combine multiple products with special pricing.',
                        'SKU count is the number of different products in the bundle.',
                        'Units per SKU is the quantity for each product.',
                        'Cart will automatically detect when customers qualify for bundle pricing.'
                    ],
                    validation: [
                        'Bundle name is required.',
                        'SKU count must be at least 2.',
                        'Units per SKU and price per unit must be greater than 0.'
                    ]
                }
            }
        };

        return contentMap[page] && contentMap[page][section] ? contentMap[page][section] : null;
    }

    function initOnboardingCard() {
        const currentPage = onboardingData.current_page || 'dashboard';
        const pageData = onboardingData.steps[ currentPage ] || {};
        const steps = pageData.steps || [];
        const completedSteps = onboardingData.progress && onboardingData.progress.completed_steps 
            ? JSON.parse( onboardingData.progress.completed_steps ) 
            : [];

        // Calculate progress
        const totalSteps = Object.values( onboardingData.steps ).reduce( (sum, page) => sum + (page.steps ? page.steps.length : 0), 0 );
        const completedCount = completedSteps.length;
        const progressPercent = totalSteps > 0 ? (completedCount / totalSteps) * 100 : 0;

        // Create floating card
        const $card = $(`
            <div class="pls-onboarding-card" id="pls-onboarding-card">
                <div class="pls-onboarding-card__header">
                    <h3>${pageData.title || 'Onboarding Guide'}</h3>
                    <button type="button" class="pls-onboarding-card__minimize" aria-label="Minimize">−</button>
                </div>
                <div class="pls-onboarding-card__body">
                    <div class="pls-onboarding-progress">
                        <div class="pls-onboarding-progress__bar">
                            <div class="pls-onboarding-progress__fill" style="width: ${progressPercent}%"></div>
                        </div>
                        <span class="pls-onboarding-progress__text">${completedCount} / ${totalSteps} steps</span>
                    </div>
                    <div class="pls-onboarding-checklist">
                        <h4>${pageData.title || 'Current Page'}</h4>
                        <ul class="pls-onboarding-checklist__list">
                            ${steps.map( (step, index) => {
                                const stepKey = currentPage + '_' + index;
                                const isCompleted = completedSteps.includes( stepKey );
                                return `
                                    <li class="pls-onboarding-checklist__item ${isCompleted ? 'is-completed' : ''}" data-step-index="${index}">
                                        <input type="checkbox" ${isCompleted ? 'checked' : ''} disabled />
                                        <span>${step}</span>
                                    </li>
                                `;
                            }).join('')}
                        </ul>
                    </div>
                </div>
                <div class="pls-onboarding-card__footer">
                    <button type="button" class="button button-small" id="pls-skip-onboarding">${PLS_Onboarding.skip_text || 'Skip'}</button>
                    <button type="button" class="button button-small" id="pls-complete-all">${PLS_Onboarding.complete_all_text || 'Complete All'}</button>
                </div>
            </div>
        `);

        $('body').append($card);

        // Make card draggable
        let isDragging = false;
        let currentX, currentY, initialX, initialY;

        $card.find('.pls-onboarding-card__header').on('mousedown', function(e) {
            if (e.target.classList.contains('pls-onboarding-card__minimize')) return;
            isDragging = true;
            initialX = e.clientX - $card.offset().left;
            initialY = e.clientY - $card.offset().top;
        });

        $(document).on('mousemove', function(e) {
            if (isDragging) {
                e.preventDefault();
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
                $card.css({
                    left: currentX + 'px',
                    top: currentY + 'px',
                    right: 'auto'
                });
            }
        });

        $(document).on('mouseup', function() {
            isDragging = false;
        });

        // Minimize/maximize
        $card.find('.pls-onboarding-card__minimize').on('click', function() {
            $card.toggleClass('is-minimized');
            $(this).text($card.hasClass('is-minimized') ? '+' : '−');
        });

        // Skip onboarding
        $('#pls-skip-onboarding').on('click', function() {
            if (confirm('Skip onboarding? You can restart it anytime from Settings.')) {
                $.ajax({
                    url: PLS_Onboarding.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pls_skip_onboarding',
                        nonce: PLS_Onboarding.nonce
                    },
                    success: function() {
                        $card.fadeOut(300, function() {
                            $(this).remove();
                            initHelpButton();
                        });
                    }
                });
            }
        });

        // Complete all
        $('#pls-complete-all').on('click', function() {
            if (confirm('Mark all steps as completed?')) {
                $.ajax({
                    url: PLS_Onboarding.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pls_complete_onboarding',
                        nonce: PLS_Onboarding.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.test_product_id) {
                            showDeleteTestProductModal(response.data.test_product_id);
                        } else {
                            $card.fadeOut(300, function() {
                                $(this).remove();
                                initHelpButton();
                            });
                        }
                    }
                });
            }
        });

        // Mark steps as completed when user interacts
        $card.find('.pls-onboarding-checklist__item').on('click', function() {
            const $item = $(this);
            if ($item.hasClass('is-completed')) return;

            const stepIndex = $item.data('step-index');
            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_update_onboarding_step',
                    nonce: PLS_Onboarding.nonce,
                    page: currentPage,
                    step_index: stepIndex
                },
                success: function() {
                    $item.addClass('is-completed').find('input').prop('checked', true);
                }
            });
        });
    }

    function initHelpButton() {
        const $helpBtn = $('<button type="button" class="button pls-help-button" id="pls-show-help">?</button>');
        $('.pls-page-head').append($helpBtn);

        $helpBtn.on('click', function() {
            const currentPage = onboardingData.current_page || 'dashboard';
            const pageData = onboardingData.steps[ currentPage ] || {};
            const steps = pageData.steps || [];

            const $helpCard = $(`
                <div class="pls-onboarding-card pls-help-card" id="pls-help-card">
                    <div class="pls-onboarding-card__header">
                        <h3>${pageData.title || 'Help'} - Quick Tips</h3>
                        <button type="button" class="pls-onboarding-card__close" aria-label="Close">×</button>
                    </div>
                    <div class="pls-onboarding-card__body">
                        <ul class="pls-onboarding-checklist__list">
                            ${steps.map( step => `<li>${step}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="pls-onboarding-card__footer">
                        <button type="button" class="button button-small" id="pls-restart-onboarding">Restart Tutorial</button>
                    </div>
                </div>
            `);

            $('body').append($helpCard);

            $helpCard.find('.pls-onboarding-card__close, #pls-restart-onboarding').on('click', function() {
                if ($(this).attr('id') === 'pls-restart-onboarding') {
                    $.ajax({
                        url: PLS_Onboarding.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'pls_start_onboarding',
                            nonce: PLS_Onboarding.nonce
                        },
                        success: function() {
                            location.reload();
                        }
                    });
                } else {
                    $helpCard.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        });
    }

    function showDeleteTestProductModal(productId) {
        const $modal = $(`
            <div class="pls-modal is-active" id="pls-delete-test-modal">
                <div class="pls-modal__dialog">
                    <div class="pls-modal__head">
                        <h2>Onboarding Complete!</h2>
                    </div>
                    <div class="pls-modal__body">
                        <p>Would you like to delete the test product you created?</p>
                    </div>
                    <div class="pls-modal__footer">
                        <button type="button" class="button" id="pls-keep-test-product">Keep It</button>
                        <button type="button" class="button button-primary" id="pls-delete-test-product" data-product-id="${productId}">Delete</button>
                    </div>
                </div>
            </div>
        `);

        $('body').append($modal);

        $('#pls-keep-test-product, #pls-delete-test-product').on('click', function() {
            const shouldDelete = $(this).attr('id') === 'pls-delete-test-product';
            
            if (shouldDelete) {
                $.ajax({
                    url: PLS_Onboarding.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pls_delete_test_product',
                        nonce: PLS_Onboarding.nonce,
                        product_id: productId
                    },
                    success: function() {
                        $modal.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                });
            } else {
                $modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
})(jQuery);
