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

        // Start tutorial button handler (banner button only - header button removed)
        $('#pls-start-tutorial-banner').on('click', function() {
            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_start_onboarding',
                    nonce: PLS_Onboarding.nonce
                },
                success: function(resp) {
                    if (resp && resp.success && resp.data && resp.data.redirect) {
                        window.location.href = resp.data.redirect;
                    } else {
                        location.reload();
                    }
                }
            });
        });

        // Skip onboarding from banner
        $('#pls-skip-onboarding-banner').on('click', function() {
            if (confirm('Skip the tutorial? You can restart it anytime from the dashboard.')) {
                $.ajax({
                    url: PLS_Onboarding.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pls_skip_onboarding',
                        nonce: PLS_Onboarding.nonce
                    },
                    success: function() {
                        $('#pls-welcome-banner').fadeOut(300, function() {
                            $(this).remove();
                        });
                        location.reload();
                    }
                });
            }
        });

        // Inject help buttons into modals
        injectModalHelpButtons();

        // Show spotlight tutorial if active (only when not in modal)
        function checkAndShowTutorial() {
            if ( onboardingData.is_active && !$('.pls-modal.is-active').length ) {
                if (!$('.pls-tutorial-overlay').length) {
                    initSpotlightTutorial();
                }
            } else {
                destroySpotlightTutorial();
            }
        }

        checkAndShowTutorial();

        // Show Help button in page header if onboarding not active
        if ( !onboardingData.is_active ) {
            initHelpButton();
        }

        // Watch for modal opens/closes
        $(document).on('click', '.pls-modal__close, .pls-modal-cancel, [data-pls-open-modal]', function() {
            setTimeout(function() {
                injectModalHelpButtons();
                checkAndShowTutorial();
            }, 300);
        });

        // Re-check tutorial on page navigation
        $(window).on('load', function() {
            setTimeout(checkAndShowTutorial, 500);
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
                                checkAndShowTutorial();
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

    // Spotlight Tutorial System
    let spotlightTutorial = {
        currentStepIndex: 0,
        steps: [],
        overlay: null,
        spotlight: null,
        tooltip: null
    };

    function initSpotlightTutorial() {
        if (!onboardingData.tutorial_flow) {
            return;
        }

        const currentPage = onboardingData.current_page || 'attributes';
        const tutorialFlow = onboardingData.tutorial_flow;
        const currentStep = tutorialFlow[currentPage];
        
        if (!currentStep) {
            return;
        }

        // Define spotlight steps for each page
        const spotlightSteps = getSpotlightSteps(currentPage);
        if (spotlightSteps.length === 0) {
            return; // No spotlight steps defined for this page
        }

        spotlightTutorial.steps = spotlightSteps;
        spotlightTutorial.currentStepIndex = 0;

        // Create overlay
        spotlightTutorial.overlay = $('<div class="pls-tutorial-overlay"></div>');
        $('body').append(spotlightTutorial.overlay);

        // Start first step
        showSpotlightStep(0);
    }

    function getSpotlightSteps(page) {
        // Define spotlight steps with selectors and messages for each page
        const stepsMap = {
            'attributes': [
                {
                    selector: '.pls-attribute-row:first',
                    title: 'Step 1 of 4: Product Options',
                    message: 'This is where you configure your product options. Let\'s start by reviewing Pack Tier settings.',
                    position: 'bottom'
                },
                {
                    selector: '.pls-admin-nav__item[href*="pls-products"]',
                    title: 'Ready for Next Step?',
                    message: 'Once you\'ve reviewed your options, click "Products" in the navigation to create your first product.',
                    position: 'bottom',
                    action: 'navigate',
                    actionUrl: 'admin.php?page=pls-products'
                }
            ],
            'products': [
                {
                    selector: '.pls-page-head .button-primary, [data-pls-open-modal="add-product"]',
                    title: 'Step 2 of 4: Create Your First Product',
                    message: 'Click this button to add a new product. We\'ll guide you through each step.',
                    position: 'bottom'
                }
            ],
            'bundles': [
                {
                    selector: '.button-primary:contains("Create Bundle"), [data-pls-open-modal="add-bundle"]',
                    title: 'Step 3 of 4: Create Bundles',
                    message: 'Click here to create a bundle. Bundles offer special pricing when customers buy multiple products.',
                    position: 'bottom'
                }
            ],
            'categories': [
                {
                    selector: '.pls-page-head, .pls-wrap h1',
                    title: 'Step 4 of 4: Review Categories',
                    message: 'Great job! Review your categories here. You\'re almost done with the tutorial!',
                    position: 'bottom'
                }
            ]
        };

        return stepsMap[page] || [];
    }

    function showSpotlightStep(stepIndex) {
        // Validate step index
        if (stepIndex < 0 || stepIndex >= spotlightTutorial.steps.length) {
            // Tutorial complete
            destroySpotlightTutorial();
            return;
        }

        const step = spotlightTutorial.steps[stepIndex];
        if (!step) {
            destroySpotlightTutorial();
            return;
        }

        // Wait for element to exist with retry logic
        waitForElement(step.selector, function($element) {
            if (!$element || $element.length === 0) {
                console.warn('Tutorial step element not found:', step.selector);
                // Skip to next step if element doesn't exist
                setTimeout(function() {
                    showSpotlightStep(stepIndex + 1);
                }, 1000);
                return;
            }

            // Ensure element is visible
            if (!$element.is(':visible')) {
                // Try scrolling to element or waiting
                $element[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function() {
                    if ($element.is(':visible')) {
                        highlightElement($element, step, stepIndex);
                    } else {
                        // Element still not visible, skip step
                        console.warn('Tutorial step element not visible:', step.selector);
                        setTimeout(function() {
                            showSpotlightStep(stepIndex + 1);
                        }, 1000);
                    }
                }, 500);
                return;
            }

            highlightElement($element, step, stepIndex);
        });
    }

    /**
     * Wait for element to exist with retry logic (up to 5 seconds).
     */
    function waitForElement(selector, callback, retries) {
        retries = retries || 0;
        const maxRetries = 10; // 10 retries * 500ms = 5 seconds max wait
        
        const $element = $(selector);
        if ($element.length > 0 && $element.is(':visible')) {
            callback($element);
            return;
        }

        if (retries >= maxRetries) {
            // Element not found after max retries
            callback(null);
            return;
        }

        setTimeout(function() {
            waitForElement(selector, callback, retries + 1);
        }, 500);
    }

    /**
     * Highlight element and show tooltip with improved error handling.
     */
    function highlightElement($element, step, stepIndex) {
        if (stepIndex >= spotlightTutorial.steps.length) {
            // Tutorial complete for this page
            completeSpotlightTutorial();
            return;
        }

        const step = spotlightTutorial.steps[stepIndex];
        if (!step || !step.selector) {
            console.warn('Tutorial step missing selector:', step);
            setTimeout(() => showSpotlightStep(stepIndex + 1), 500);
            return;
        }

        // Wait for element with retry logic (up to 5 seconds)
        waitForElementWithRetry(step.selector, function($target) {
            if (!$target || $target.length === 0) {
                console.warn('Tutorial step element not found after retries:', step.selector);
                // Skip to next step if element doesn't exist
                setTimeout(() => showSpotlightStep(stepIndex + 1), 500);
                return;
            }

            // Ensure element is visible
            if (!$target.is(':visible')) {
                // Try scrolling to element
                try {
                    $target[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                } catch (e) {
                    // Fallback scroll
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 500);
                }
                
                // Wait and check visibility again
                setTimeout(() => {
                    if ($target.is(':visible')) {
                        highlightElement($target, step);
                    } else {
                        console.warn('Tutorial step element not visible after scroll:', step.selector);
                        // Skip step if still not visible
                        setTimeout(() => showSpotlightStep(stepIndex + 1), 500);
                    }
                }, 600);
                return;
            }

            // Element found and visible, highlight it
            try {
                // Scroll to element smoothly
                $target[0].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                setTimeout(() => {
                    highlightElement($target, step);
                }, 600);
            } catch (e) {
                // Fallback scroll
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 500);
                setTimeout(() => {
                    highlightElement($target, step);
                }, 600);
            }
        });
    }

    /**
     * Wait for element to exist with retry logic (up to 5 seconds).
     */
    function waitForElementWithRetry(selector, callback, retries) {
        retries = retries || 0;
        const maxRetries = 10; // 10 retries * 500ms = 5 seconds max wait
        
        try {
            const $element = $(selector).first();
            if ($element.length > 0 && $element.is(':visible')) {
                callback($element);
                return;
            }
        } catch (e) {
            console.warn('Error checking element:', selector, e);
        }

        if (retries >= maxRetries) {
            // Element not found after max retries
            callback(null);
            return;
        }

        setTimeout(() => {
            waitForElementWithRetry(selector, callback, retries + 1);
        }, 500);
    }

    function highlightElement($element, step) {
        // Validate element exists and is visible
        if (!$element || $element.length === 0 || !$element.is(':visible')) {
            console.warn('Cannot highlight element - not found or not visible');
            return;
        }

        // Remove existing spotlight
        $('.pls-tutorial-spotlight').remove();
        $('.pls-tutorial-tooltip').remove();

        try {
            // Get element position with error handling
            const offset = $element.offset();
            if (!offset) {
                console.warn('Cannot get element offset');
                return;
            }

            const width = Math.max($element.outerWidth() || 0, 10);
            const height = Math.max($element.outerHeight() || 0, 10);

            // Create spotlight
            spotlightTutorial.spotlight = $('<div class="pls-tutorial-spotlight"></div>');
            spotlightTutorial.spotlight.css({
                top: offset.top + 'px',
                left: offset.left + 'px',
                width: width + 'px',
                height: height + 'px'
            });
            $('body').append(spotlightTutorial.spotlight);

            // Add highlight class to element
            $element.addClass('pls-tutorial-highlighted');

            // Create tooltip
            createTooltip($element, step);
        } catch (e) {
            console.error('Error highlighting element:', e);
            // Continue to next step on error
            setTimeout(() => {
                nextSpotlightStep();
            }, 500);
        }
    }

    function createTooltip($element, step) {
        const offset = $element.offset();
        const width = $element.outerWidth();
        const height = $element.outerHeight();
        const tooltipWidth = 400;
        const tooltipHeight = 200;
        const spacing = 20;

        let tooltipTop, tooltipLeft, arrowClass, arrowStyle = '';

        // Position tooltip based on step.position
        if (step.position === 'bottom' || !step.position) {
            tooltipTop = offset.top + height + spacing;
            tooltipLeft = offset.left + (width / 2) - (tooltipWidth / 2);
            arrowClass = 'pls-tutorial-tooltip__arrow--top';
            arrowStyle = `left: ${tooltipWidth / 2 - 12}px;`;
        } else if (step.position === 'top') {
            tooltipTop = offset.top - tooltipHeight - spacing;
            tooltipLeft = offset.left + (width / 2) - (tooltipWidth / 2);
            arrowClass = 'pls-tutorial-tooltip__arrow--bottom';
            arrowStyle = `left: ${tooltipWidth / 2 - 12}px;`;
        } else if (step.position === 'right') {
            tooltipTop = offset.top + (height / 2) - (tooltipHeight / 2);
            tooltipLeft = offset.left + width + spacing;
            arrowClass = 'pls-tutorial-tooltip__arrow--left';
            arrowStyle = `top: ${tooltipHeight / 2 - 12}px;`;
        } else { // left
            tooltipTop = offset.top + (height / 2) - (tooltipHeight / 2);
            tooltipLeft = offset.left - tooltipWidth - spacing;
            arrowClass = 'pls-tutorial-tooltip__arrow--right';
            arrowStyle = `top: ${tooltipHeight / 2 - 12}px;`;
        }

        // Keep tooltip in viewport
        if (tooltipLeft < 20) tooltipLeft = 20;
        if (tooltipLeft + tooltipWidth > $(window).width() - 20) {
            tooltipLeft = $(window).width() - tooltipWidth - 20;
        }
        if (tooltipTop < 20) tooltipTop = 20;
        if (tooltipTop + tooltipHeight > $(window).height() - 20) {
            tooltipTop = $(window).height() - tooltipHeight - 20;
        }

        const isLastStep = spotlightTutorial.currentStepIndex === spotlightTutorial.steps.length - 1;
        const nextButtonText = step.action === 'navigate' ? 'Continue' : (isLastStep ? 'Got it!' : 'Next');

        spotlightTutorial.tooltip = $(`
            <div class="pls-tutorial-tooltip">
                <div class="pls-tutorial-tooltip__arrow ${arrowClass}" style="${arrowStyle}"></div>
                <div class="pls-tutorial-tooltip__header">
                    <div>
                        <div class="pls-tutorial-tooltip__step">${step.title}</div>
                        <h3 class="pls-tutorial-tooltip__title">${step.title}</h3>
                    </div>
                </div>
                <div class="pls-tutorial-tooltip__body">${step.message}</div>
                <div class="pls-tutorial-tooltip__footer">
                    <button type="button" class="pls-tutorial-tooltip__skip" id="pls-spotlight-skip">Skip Tutorial</button>
                    <button type="button" class="pls-tutorial-tooltip__next" id="pls-spotlight-next">${nextButtonText}</button>
                </div>
            </div>
        `);

        spotlightTutorial.tooltip.css({
            top: tooltipTop + 'px',
            left: tooltipLeft + 'px'
        });

        $('body').append(spotlightTutorial.tooltip);

        // Attach handlers
        $('#pls-spotlight-next').on('click', function() {
            if (step.action === 'navigate' && step.actionUrl) {
                const adminBase = PLS_Onboarding.admin_url || (window.location.origin + '/wp-admin/');
                window.location.href = adminBase + step.actionUrl;
            } else {
                nextSpotlightStep();
            }
        });

        $('#pls-spotlight-skip').on('click', function() {
            if (confirm('Skip the tutorial? You can restart it anytime from the dashboard.')) {
                skipSpotlightTutorial();
            }
        });
    }

    function nextSpotlightStep() {
        spotlightTutorial.currentStepIndex++;
        $('.pls-tutorial-spotlight').remove();
        $('.pls-tutorial-tooltip').remove();
        $('.pls-tutorial-highlighted').removeClass('pls-tutorial-highlighted');
        
        if (spotlightTutorial.currentStepIndex < spotlightTutorial.steps.length) {
            showSpotlightStep(spotlightTutorial.currentStepIndex);
        } else {
            completeSpotlightTutorial();
        }
    }

    function completeSpotlightTutorial() {
        destroySpotlightTutorial();
        
        // Check if we should navigate to next page
        const currentPage = onboardingData.current_page || 'attributes';
        const tutorialFlow = onboardingData.tutorial_flow;
        const currentStep = tutorialFlow[currentPage];
        
        if (currentStep && currentStep.next_page) {
            const nextUrl = getAdminUrlForPage(currentStep.next_page);
            if (nextUrl) {
                setTimeout(() => {
                    window.location.href = nextUrl;
                }, 500);
            }
        } else {
            // Tutorial complete
            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_complete_onboarding',
                    nonce: PLS_Onboarding.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tutorial completed! You\'re all set to start creating products.');
                        location.reload();
                    }
                }
            });
        }
    }

    function skipSpotlightTutorial() {
        destroySpotlightTutorial();
        $.ajax({
            url: PLS_Onboarding.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_skip_onboarding',
                nonce: PLS_Onboarding.nonce
            },
            success: function() {
                location.reload();
            }
        });
    }

    function destroySpotlightTutorial() {
        $('.pls-tutorial-overlay').remove();
        $('.pls-tutorial-spotlight').remove();
        $('.pls-tutorial-tooltip').remove();
        $('.pls-tutorial-highlighted').removeClass('pls-tutorial-highlighted');
        spotlightTutorial.currentStepIndex = 0;
        spotlightTutorial.steps = [];
    }

    function getPreviousPage(currentPage, tutorialFlow) {
        const pages = Object.keys(tutorialFlow);
        const currentIndex = pages.indexOf(currentPage);
        return currentIndex > 0 ? pages[currentIndex - 1] : null;
    }

    function attachTutorialHandlers(currentPage, tutorialFlow, completedSteps) {
        const stepKey = currentPage + '_';

        // Step checkbox handlers
        $('.pls-tutorial-panel__step-item').on('click', function() {
            const $item = $(this);
            const stepIndex = $item.data('step-index');
            const subStepKey = stepKey + stepIndex;
            const isCompleted = $item.hasClass('is-completed');

            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_update_onboarding_step',
                    nonce: PLS_Onboarding.nonce,
                    page: currentPage,
                    step_index: stepIndex,
                    mark_completed: !isCompleted
                },
                success: function() {
                    $item.toggleClass('is-completed');
                    $item.find('input').prop('checked', !isCompleted);
                    updateNextButton();
                }
            });
        });

        // Next button handler
        $('#pls-tutorial-next').on('click', function() {
            const nextPage = $(this).data('next-page');
            if (!nextPage) {
                // Complete tutorial
                completeTutorial();
            } else {
                // Navigate to next page
                const nextUrl = getAdminUrlForPage(nextPage);
                if (nextUrl) {
                    window.location.href = nextUrl;
                }
            }
        });

        // Previous button handler
        $('.pls-tutorial-panel__prev-btn').on('click', function() {
            const prevPage = $(this).data('prev-page');
            if (prevPage) {
                const prevUrl = getAdminUrlForPage(prevPage);
                if (prevUrl) {
                    window.location.href = prevUrl;
                }
            }
        });

        // Skip button handler
        $('#pls-tutorial-skip').on('click', function() {
            if (confirm('Skip the tutorial? You can restart it anytime from the dashboard.')) {
                $.ajax({
                    url: PLS_Onboarding.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pls_skip_onboarding',
                        nonce: PLS_Onboarding.nonce
                    },
                    success: function() {
                        $('#pls-tutorial-panel').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                });
            }
        });

        function updateNextButton() {
            const currentCompleted = $('.pls-tutorial-panel__step-item.is-completed').length;
            const totalSubSteps = $('.pls-tutorial-panel__step-item').length;
            const canProceed = currentCompleted >= Math.min(3, totalSubSteps);
            $('#pls-tutorial-next').prop('disabled', !canProceed);
        }
    }

    function getAdminUrlForPage(page) {
        const pageMap = {
            'attributes': 'admin.php?page=pls-attributes',
            'products': 'admin.php?page=pls-products',
            'bundles': 'admin.php?page=pls-bundles',
            'categories': 'admin.php?page=pls-categories'
        };
        const adminBase = PLS_Onboarding.admin_url || (window.location.origin + '/wp-admin/');
        return pageMap[page] ? adminBase + pageMap[page] : null;
    }

    function completeTutorial() {
        $.ajax({
            url: PLS_Onboarding.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_complete_onboarding',
                nonce: PLS_Onboarding.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#pls-tutorial-panel').fadeOut(300, function() {
                        alert('Tutorial completed! You\'re all set to start creating products.');
                        location.reload();
                    });
                }
            }
        });
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
    // Exploration system
    function initExplorationSystem() {
        if (!onboardingData || !onboardingData.exploration_flows) {
            return;
        }

        // Handle "Take Tour" button clicks
        $('.pls-exploration-start').on('click', function() {
            const explorationKey = $(this).data('exploration-key');
            if (!explorationKey) return;

            const flow = onboardingData.exploration_flows[explorationKey];
            if (!flow) return;

            // Navigate to the exploration page
            const pageMap = {
                'custom-orders': 'admin.php?page=pls-custom-orders',
                'revenue': 'admin.php?page=pls-revenue',
                'commission': 'admin.php?page=pls-commission',
                'bi-dashboard': 'admin.php?page=pls-bi'
            };

            // Store exploration key in sessionStorage to trigger panel on page load
            sessionStorage.setItem('pls_active_exploration', explorationKey);

            const adminBase = PLS_Onboarding.admin_url || (window.location.origin + '/wp-admin/');
            const targetUrl = pageMap[explorationKey] ? adminBase + pageMap[explorationKey] : null;

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });

        // Check if we should show exploration panel on current page
        const activeExploration = sessionStorage.getItem('pls_active_exploration');
        if (activeExploration && onboardingData.exploration_flows[activeExploration]) {
            const flow = onboardingData.exploration_flows[activeExploration];
            if (flow.page === onboardingData.current_page) {
                setTimeout(function() {
                    initExplorationPanel(activeExploration, flow);
                    sessionStorage.removeItem('pls_active_exploration');
                }, 500);
            }
        }
    }

    function initExplorationPanel(explorationKey, flow) {
        // Remove any existing exploration panel
        $('#pls-exploration-panel').remove();

        const exploredFeatures = onboardingData.explored_features || [];
        const isExplored = exploredFeatures.indexOf(explorationKey) !== -1;

        // Build steps HTML
        const stepsHtml = flow.steps.map((step, index) => {
            return `
                <li class="pls-exploration-panel__step-item" data-step-index="${index}">
                    <input type="checkbox" class="pls-exploration-panel__step-checkbox" disabled />
                    <span class="pls-exploration-panel__step-text">${step}</span>
                </li>
            `;
        }).join('');

        // Build panel HTML
        const panelHtml = `
            <div class="pls-exploration-panel" id="pls-exploration-panel">
                <div class="pls-exploration-panel__header">
                    <div class="pls-exploration-panel__header-left">
                        <h3 class="pls-exploration-panel__title">${flow.title}</h3>
                        ${isExplored ? '<span class="pls-exploration-card__badge">Completed</span>' : ''}
                    </div>
                    <button type="button" class="pls-exploration-panel__close" aria-label="Close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--pls-gray-600); padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
                <div class="pls-exploration-panel__body">
                    <p class="pls-exploration-panel__description">${flow.description}</p>
                    <ul class="pls-exploration-panel__steps">
                        ${stepsHtml}
                    </ul>
                </div>
                <div class="pls-exploration-panel__footer">
                    <button type="button" class="pls-exploration-panel__skip-btn" id="pls-exploration-skip">Skip Tour</button>
                    <div class="pls-exploration-panel__nav-buttons">
                        <button type="button" class="button button-primary pls-exploration-panel__complete-btn" id="pls-exploration-complete">
                            ${isExplored ? 'Mark as Complete' : 'Complete Tour'}
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insert at top of page content
        const $pageHead = $('.pls-page-head');
        if ($pageHead.length) {
            $pageHead.after(panelHtml);
        } else {
            $('.pls-wrap').prepend(panelHtml);
        }

        // Attach event handlers
        attachExplorationHandlers(explorationKey);
    }

    function attachExplorationHandlers(explorationKey) {
        // Close button (use event delegation to handle dynamically added elements)
        $(document).off('click', '.pls-exploration-panel__close, #pls-exploration-skip').on('click', '.pls-exploration-panel__close, #pls-exploration-skip', function() {
            $('#pls-exploration-panel').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Complete button
        $('#pls-exploration-complete').on('click', function() {
            $.ajax({
                url: PLS_Onboarding.ajax_url,
                type: 'POST',
                data: {
                    action: 'pls_complete_exploration',
                    nonce: PLS_Onboarding.nonce,
                    exploration_key: explorationKey
                },
                success: function(resp) {
                    if (resp && resp.success) {
                        $('#pls-exploration-panel').fadeOut(300, function() {
                            $(this).remove();
                            // Update explored features in local data
                            if (onboardingData) {
                                onboardingData.explored_features = resp.data.explored_features || [];
                            }
                            // Reload to show updated badge
                            location.reload();
                        });
                    }
                },
                error: function() {
                    alert('Could not complete exploration. Please try again.');
                }
            });
        });

        // Step checkbox handlers (mark steps as read)
        $('.pls-exploration-panel__step-item').on('click', function() {
            $(this).toggleClass('is-completed');
            $(this).find('input').prop('checked', $(this).hasClass('is-completed'));
        });
    }

    // Initialize exploration system
    if (onboardingData && onboardingData.exploration_flows) {
        initExplorationSystem();
    }

})(jQuery);
