/**
 * Simplified Help System JavaScript.
 * Provides a simple help panel accessible from each page.
 */

(function($) {
    'use strict';

    let helpPanelOpen = false;

    $(document).ready(function() {
        if ( ! PLS_Onboarding || ! PLS_Onboarding.current_page ) {
            return;
        }

        // Add help button to page header
        initHelpButton();
    });

    /**
     * Initialize help button in page header.
     */
    function initHelpButton() {
        // Check if help button already exists
        if ($('#pls-help-button').length) {
            return;
        }

        // Create help button
        const $helpBtn = $('<button type="button" class="button pls-help-button" id="pls-help-button" title="View Help Guide">?</button>');
        
        // Insert into page header
        const $pageHead = $('.pls-page-head');
        if ($pageHead.length) {
            $pageHead.append($helpBtn);
        } else {
            // Fallback: insert at top of page
            $('.pls-wrap').first().prepend($helpBtn);
        }

        // Attach click handler
        $helpBtn.on('click', function(e) {
            e.preventDefault();
            toggleHelpPanel();
        });
    }

    /**
     * Toggle help panel visibility.
     */
    function toggleHelpPanel() {
        if (helpPanelOpen) {
            closeHelpPanel();
        } else {
            openHelpPanel();
        }
    }

    /**
     * Open help panel with current page guide.
     */
    function openHelpPanel() {
        const currentPage = PLS_Onboarding.current_page || 'dashboard';

        // Remove existing panel
        $('#pls-help-panel').remove();

        // Fetch help content
        $.ajax({
            url: PLS_Onboarding.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_get_helper_content',
                nonce: PLS_Onboarding.nonce,
                page: currentPage
            },
            success: function(resp) {
                if (resp && resp.success && resp.data && resp.data.content) {
                    renderHelpPanel(resp.data.content);
                    helpPanelOpen = true;
                } else {
                    alert('Unable to load help content. Please try again.');
                }
            },
            error: function() {
                alert('Error loading help content. Please refresh the page and try again.');
            }
        });
    }

    /**
     * Render help panel with content.
     */
    function renderHelpPanel(content) {
        let sectionsHtml = '';

        if (content.sections && content.sections.length) {
            content.sections.forEach(function(section) {
                let itemsHtml = '';
                
                if (section.items && section.items.length) {
                    itemsHtml = '<ul class="pls-help-section__items">';
                    section.items.forEach(function(item) {
                        itemsHtml += '<li>' + item + '</li>';
                    });
                    itemsHtml += '</ul>';
                }

                sectionsHtml += `
                    <div class="pls-help-section">
                        <h3 class="pls-help-section__title">${section.title}</h3>
                        ${section.content ? '<p class="pls-help-section__content">' + section.content + '</p>' : ''}
                        ${itemsHtml}
                    </div>
                `;
            });
        }

        const panelHtml = `
            <div class="pls-help-panel" id="pls-help-panel">
                <div class="pls-help-panel__overlay"></div>
                <div class="pls-help-panel__content">
                    <div class="pls-help-panel__header">
                        <h2 class="pls-help-panel__title">${content.title || 'Help Guide'}</h2>
                        <button type="button" class="pls-help-panel__close" id="pls-help-close" aria-label="Close Help">×</button>
                    </div>
                    <div class="pls-help-panel__body">
                        ${sectionsHtml}
                    </div>
                </div>
            </div>
        `;

        $('body').append(panelHtml);

        // Attach close handlers
        $('#pls-help-close, .pls-help-panel__overlay').on('click', function() {
            closeHelpPanel();
        });

        // Close on Escape key
        $(document).on('keydown.pls-help', function(e) {
            if (e.key === 'Escape' && helpPanelOpen) {
                closeHelpPanel();
            }
        });

        // Scroll to top of panel
        $('.pls-help-panel__body').scrollTop(0);
    }

    /**
     * Close help panel.
     */
    function closeHelpPanel() {
        $('#pls-help-panel').fadeOut(300, function() {
            $(this).remove();
        });
        $(document).off('keydown.pls-help');
        helpPanelOpen = false;
    }

})(jQuery);
