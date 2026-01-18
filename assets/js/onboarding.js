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

        // Show onboarding card if active
        if ( onboardingData.is_active ) {
            initOnboardingCard();
        } else {
            // Show Help button
            initHelpButton();
        }
    });

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
