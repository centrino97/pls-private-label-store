(function($){
  function loadOffers($root){
    $.post(PLS_Offers.ajax_url, { action:'pls_get_offers', nonce: PLS_Offers.nonce }, function(res){
      if(!res || !res.success) return;
      var offers = (res.data && res.data.offers) ? res.data.offers : [];
      var $list = $root.find('[data-pls-offer-list]');
      if(!$list.length) return;
      $list.empty();
      if(!offers.length){
        $list.append($('<div/>').addClass('pls-muted').text('No offers.'));
        return;
      }
      offers.forEach(function(o){
        var $card = $('<div/>').css({padding:'10px',border:'1px solid rgba(0,0,0,.12)',borderRadius:'10px',marginBottom:'10px'});
        $card.append($('<div/>').css({fontWeight:600}).text(o.title || 'Offer'));
        if(o.description){ $card.append($('<div/>').addClass('pls-muted').text(o.description)); }
        var $btn = $('<button/>').addClass('pls-offer__btn').text('Apply').on('click', function(){
          $.post(PLS_Offers.ajax_url, { action:'pls_apply_offer', nonce: PLS_Offers.nonce, offer_id:o.id }, function(r){
            // For now, just reload page if success in future.
            if(r && r.success){ alert(r.data && r.data.message ? r.data.message : 'Applied'); }
          });
        });
        $card.append($btn);
        $list.append($card);
      });
    });
  }

  $(document).on('click', '[data-pls-offer-widget]', function(){
    // no-op
  });

  // Tier card selection handler
  function initTierCards() {
    // Hide default WooCommerce variation selector if tier cards are present
    const $tierCards = $('.pls-tier-cards');
    if ($tierCards.length) {
      // Hide the pack-tier variation selector (we replace it with cards)
      $('.variations tr:has(select[name="attribute_pa_pack-tier"]), .variations tr:has(input[name="attribute_pa_pack-tier"])').hide();
      
      // Also hide the variation table if it only has pack-tier
      const $variationTable = $('.variations');
      if ($variationTable.length) {
        const visibleRows = $variationTable.find('tr:visible').length;
        if (visibleRows === 0) {
          $variationTable.closest('table, .woocommerce-variation').hide();
        }
      }
    }
    
    $('.pls-tier-card__select').on('click', function(e) {
      e.preventDefault();
      
      const $card = $(this).closest('.pls-tier-card');
      const tierSlug = $card.data('tier');
      const variationId = $card.data('variation-id');
      
      if (!tierSlug || !variationId) {
        console.warn('PLS: Missing tier or variation ID');
        return;
      }
      
      // Remove selected state from all cards
      $('.pls-tier-card').removeClass('is-selected');
      
      // Add selected state to clicked card
      $card.addClass('is-selected');
      
      // Find WooCommerce variation form
      const $form = $('form.variations_form, form.cart');
      if (!$form.length) {
        console.warn('PLS: WooCommerce form not found');
        return;
      }
      
      // Method 1: Set the pack-tier attribute value (preferred)
      let variationSelected = false;
      
      // Try select dropdown
      const $select = $form.find('select[name="attribute_pa_pack-tier"]');
      if ($select.length) {
        $select.val(tierSlug).trigger('change');
        variationSelected = true;
      }
      
      // Try radio buttons
      if (!variationSelected) {
        const $radio = $form.find('input[type="radio"][name="attribute_pa_pack-tier"][value="' + tierSlug + '"]');
        if ($radio.length) {
          $radio.prop('checked', true).trigger('change');
          variationSelected = true;
        }
      }
      
      // Method 2: Direct variation selection (fallback)
      if (!variationSelected) {
        // Set variation_id directly
        let $variationInput = $form.find('input[name="variation_id"]');
        if (!$variationInput.length) {
          // Create hidden input if it doesn't exist
          $variationInput = $('<input>').attr({
            type: 'hidden',
            name: 'variation_id',
            value: variationId
          });
          $form.append($variationInput);
        } else {
          $variationInput.val(variationId);
        }
        
        // Trigger WooCommerce variation change
        $form.trigger('check_variations');
        $form.trigger('found_variation', [variationId]);
        
        // Update price display
        const $variationPrice = $('.woocommerce-variation-price');
        if ($variationPrice.length && $card.find('.pls-tier-card__price').length) {
          const priceHtml = $card.find('.pls-tier-card__price').html();
          $variationPrice.find('.price').html(priceHtml);
        }
      }
      
      // Scroll to add to cart button smoothly
      setTimeout(function() {
        const $addToCart = $('.single_add_to_cart_button, button[type="submit"][name="add-to-cart"]');
        if ($addToCart.length && $addToCart.is(':visible')) {
          $('html, body').animate({
            scrollTop: $addToCart.offset().top - 150
          }, 400);
        }
      }, 300);
    });
    
    // Sync WooCommerce variation changes back to tier cards
    $(document.body).on('found_variation', function(event, variation) {
      if (variation && variation.variation_id) {
        const $card = $('.pls-tier-card[data-variation-id="' + variation.variation_id + '"]');
        if ($card.length) {
          $('.pls-tier-card').removeClass('is-selected');
          $card.addClass('is-selected');
        }
      }
    });
    
    // Handle variation clearing
    $(document.body).on('reset_data', function() {
      $('.pls-tier-card').removeClass('is-selected');
    });
    
    // Trigger event for dynamic loading
    $(document).trigger('pls_tier_cards_initialized');
  }

  // Image gallery thumbnail click handler
  function initImageGallery() {
    $('.pls-gallery-thumb').on('click', function() {
      const $thumb = $(this);
      const imageUrl = $thumb.data('image-url');
      const imageId = $thumb.data('image-id');
      
      if (!imageUrl) return;
      
      // Update main image
      const $mainImg = $('.pls-product-image-main__img');
      if ($mainImg.length) {
        $mainImg.attr('src', imageUrl);
        $mainImg.attr('data-image-id', imageId);
      }
      
      // Update active state
      $('.pls-gallery-thumb').removeClass('is-active');
      $thumb.addClass('is-active');
    });
  }

  // Price calculator
  function initPriceCalculator() {
    let selectedTier = null;
    let selectedOptions = {};
    // Quantity is always 1 (one pack selected)
    const quantity = 1;

    function calculatePrice() {
      if (!selectedTier) {
        updatePriceDisplay(0, 0, 0, 0);
        updateUnitsDisplay(0);
        return;
      }

      const totalPriceForTier = parseFloat(selectedTier.totalPrice) || 0;
      const units = parseInt(selectedTier.units) || 0;
      
      // Calculate base price PER UNIT (variation price is total, divide by units)
      const basePricePerUnit = units > 0 ? totalPriceForTier / units : 0;
      
      // Calculate options price PER UNIT (options are already per unit)
      let optionsTotalPerUnit = 0;
      Object.values(selectedOptions).forEach(option => {
        if (!option || !option.price) return;
        
        // Check for tier-specific pricing (already per unit)
        let optionPricePerUnit = parseFloat(option.price) || 0;
        if (option.tierPrices && selectedTier.tierKey) {
          const tierNum = parseInt(selectedTier.tierKey.replace('tier_', '')) || 1;
          if (option.tierPrices[tierNum]) {
            optionPricePerUnit = parseFloat(option.tierPrices[tierNum]);
          }
        }
        
        optionsTotalPerUnit += optionPricePerUnit;
      });

      // Total price per unit = base per unit + options per unit
      const totalPerUnit = basePricePerUnit + optionsTotalPerUnit;
      
      // Total for this pack = (price per unit) * (units per pack) * (quantity = 1)
      const totalForOrder = totalPerUnit * units * quantity;
      
      // Base total for pack = base per unit * units * quantity
      const baseTotalForOrder = basePricePerUnit * units * quantity;
      
      // Options total for pack = options per unit * units * quantity
      const optionsTotalForOrder = optionsTotalPerUnit * units * quantity;

      updatePriceDisplay(baseTotalForOrder, optionsTotalForOrder, totalForOrder, totalPerUnit);
      updateUnitsDisplay(units);
    }

    function updatePriceDisplay(base, options, total, perUnit) {
      $('#pls-price-base').html(formatPrice(base));
      if (options > 0) {
        $('#pls-price-options-row').show();
        $('#pls-price-options').html(formatPrice(options));
      } else {
        $('#pls-price-options-row').hide();
      }
      $('#pls-price-total').html(formatPrice(total));
      $('#pls-price-per-unit').html(formatPrice(perUnit));
    }

    function updateUnitsDisplay(units) {
      $('#pls-selected-units').text(units.toLocaleString());
    }

    function formatPrice(amount) {
      const numAmount = parseFloat(amount) || 0;
      
      // Try to use WooCommerce formatting if available
      if (typeof wc_add_to_cart_params !== 'undefined') {
        const decimals = wc_add_to_cart_params.currency_format_num_decimals || 2;
        const symbol = wc_add_to_cart_params.currency_format_symbol || '$';
        const decimalSep = wc_add_to_cart_params.currency_format_decimal_sep || '.';
        const thousandSep = wc_add_to_cart_params.currency_format_thousand_sep || ',';
        const format = wc_add_to_cart_params.currency_format || '%1$s%2$s';
        
        const formatted = numAmount.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
        return format.replace('%1$s', symbol).replace('%2$s', formatted);
      }
      
      // Fallback to simple formatting
      return '$' + numAmount.toFixed(2);
    }

    // Tier selection handler
    $(document).on('click', '.pls-tier-card__select', function() {
      const $card = $(this).closest('.pls-tier-card');
      const tierSlug = $card.data('tier');
      const variationId = $card.data('variation-id');
      const units = parseInt($card.data('units')) || 0;
      const pricePerUnit = parseFloat($card.data('price-per-unit')) || 0;
      const totalPrice = parseFloat($card.data('total-price')) || 0;
      
      // Get tier key for option pricing
      const tierKey = $card.find('.pls-tier-card__badge').attr('class')?.match(/tier_\d+/)?.[0] || 'tier_1';

      selectedTier = {
        slug: tierSlug,
        variationId: variationId,
        units: units,
        pricePerUnit: pricePerUnit,
        totalPrice: totalPrice,
        tierKey: tierKey
      };
      
      // Update variation input
      $('.pls-variation-id').val(variationId);

      // Enable add to cart button
      const $addToCartBtn = $('.pls-add-to-cart-button');
      if ($addToCartBtn.length) {
        $addToCartBtn.prop('disabled', false);
        $addToCartBtn.find('.pls-add-to-cart-text').text('Add to Cart');
      }

      calculatePrice();
    });

    // Option selection handler
    $(document).on('change', '.pls-option-value-card input[type="radio"]', function() {
      const $card = $(this).closest('.pls-option-value-card');
      const $group = $card.closest('.pls-product-option-group');
      const attributeLabel = $group.data('attribute-label');
      const valueId = $(this).data('value-id');
      const price = parseFloat($(this).data('price')) || 0;
      const tierPricesJson = $(this).data('tier-prices');
      const tierPrices = tierPricesJson ? JSON.parse(tierPricesJson) : null;

      // Remove selected state from other options in same group
      $group.find('.pls-option-value-card').removeClass('is-selected');
      $card.addClass('is-selected');

      selectedOptions[attributeLabel] = {
        valueId: valueId,
        price: price,
        tierPrices: tierPrices
      };

      calculatePrice();
      
      // Visual feedback - scroll price summary into view if needed
      const $priceSummary = $('.pls-price-summary');
      if ($priceSummary.length && !isElementInViewport($priceSummary[0])) {
        $('html, body').animate({
          scrollTop: $priceSummary.offset().top - 100
        }, 300);
      }
    });
    
    // Helper function to check if element is in viewport
    function isElementInViewport(el) {
      const rect = el.getBoundingClientRect();
      return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
      );
    }

  }

  // Add to cart handler
  function initAddToCart() {
    $('.pls-cart-form').on('submit', function(e) {
      e.preventDefault();

      const $form = $(this);
      const $button = $form.find('.pls-add-to-cart-button');
      const $messages = $('#pls-cart-messages');
      const productId = $form.data('product_id');
      const variationId = $('.pls-variation-id').val();
      const quantity = 1; // Always 1 pack

      if (!variationId) {
        $messages.html('<div class="pls-message-error">Please select a pack size.</div>').addClass('pls-message-error');
        return;
      }

      // Disable button and show loading
      $button.prop('disabled', true);
      $button.find('.pls-add-to-cart-text').text('Adding...');
      $messages.empty().removeClass('pls-message-success pls-message-error');

      // AJAX add to cart
      const ajaxUrl = (typeof plsOffers !== 'undefined' && plsOffers.ajaxUrl) 
        ? plsOffers.ajaxUrl
        : '/wp-admin/admin-ajax.php';

      $.ajax({
        type: 'POST',
        url: ajaxUrl,
        data: {
          action: 'pls_add_to_cart',
          product_id: productId,
          variation_id: variationId,
          quantity: quantity,
          nonce: (typeof plsOffers !== 'undefined' && plsOffers.addToCartNonce ? plsOffers.addToCartNonce : '')
        },
        success: function(response) {
          if (response.success) {
            $messages.html('<div class="pls-message-success">✓ Added to cart successfully!</div>').addClass('pls-message-success');
            $button.find('.pls-add-to-cart-text').text('Added!');
            
            // Trigger cart update event
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
            
            // Close configurator modal if open
            $('#pls-configurator-modal').removeClass('is-visible');
            $('body').removeClass('pls-modal-open');
            
            // Show bundle popup after short delay
            setTimeout(function() {
              showBundlePopup();
            }, 500);
          } else {
            $messages.html('<div class="pls-message-error">' + (response.data?.message || 'Failed to add to cart.') + '</div>').addClass('pls-message-error');
            $button.prop('disabled', false);
            $button.find('.pls-add-to-cart-text').text('Add to Cart');
          }
        },
        error: function() {
          $messages.html('<div class="pls-message-error">An error occurred. Please try again.</div>').addClass('pls-message-error');
          $button.prop('disabled', false);
          $button.find('.pls-add-to-cart-text').text('Add to Cart');
        }
      });
    });
  }

  // Bundle popup
  function showBundlePopup() {
    // Check if there are applicable bundles
    const $popup = $('.pls-bundle-popup');
    if (!$popup.length) {
      // Create popup HTML if it doesn't exist
      // Get cart URL from various sources
      const cartUrl = (typeof plsOffers !== 'undefined' && plsOffers.cartUrl) 
        ? plsOffers.cartUrl 
        : ((typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.cart_url) 
          ? wc_add_to_cart_params.cart_url 
          : '/cart');
      
      const popupHtml = `
        <div class="pls-bundle-popup">
          <div class="pls-bundle-popup__content">
            <button type="button" class="pls-bundle-popup__close" aria-label="Close">×</button>
            <h2 class="pls-bundle-popup__title">✓ Added to Cart!</h2>
            <p class="pls-bundle-popup__message">Complete your order with a bundle and save more!</p>
            <div class="pls-bundle-popup__actions">
              <a href="${cartUrl}" class="button button-primary">View Cart</a>
              <button type="button" class="button pls-bundle-popup-continue">Continue Shopping</button>
            </div>
          </div>
        </div>
      `;
      $('body').append(popupHtml);
    }

    $('.pls-bundle-popup').addClass('is-visible');

    // Close handlers
    $('.pls-bundle-popup__close, .pls-bundle-popup-continue').on('click', function() {
      $('.pls-bundle-popup').removeClass('is-visible');
    });

    // Close on background click
    $('.pls-bundle-popup').on('click', function(e) {
      if ($(e.target).hasClass('pls-bundle-popup')) {
        $(this).removeClass('is-visible');
      }
    });
  }

  // Tab switching
  function initTabs() {
    $('.pls-tab-button').on('click', function() {
      const tabName = $(this).data('tab');
      
      // Update buttons
      $('.pls-tab-button').removeClass('is-active');
      $(this).addClass('is-active');
      
      // Update panels
      $('.pls-tab-panel').removeClass('is-active');
      $('.pls-tab-panel[data-tab="' + tabName + '"]').addClass('is-active');
    });
  }

  // Configurator Modal
  function initConfiguratorModal() {
    // Open modal
    $(document).on('click', '#pls-open-configurator', function() {
      $('#pls-configurator-modal').addClass('is-visible');
      $('body').addClass('pls-modal-open');
    });

    // Close modal
    $(document).on('click', '.pls-configurator-modal__close, .pls-configurator-modal__overlay', function(e) {
      if ($(e.target).hasClass('pls-configurator-modal__overlay') || $(e.target).hasClass('pls-configurator-modal__close')) {
        $('#pls-configurator-modal').removeClass('is-visible');
        $('body').removeClass('pls-modal-open');
      }
    });

    // Close on ESC key
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $('#pls-configurator-modal').hasClass('is-visible')) {
        $('#pls-configurator-modal').removeClass('is-visible');
        $('body').removeClass('pls-modal-open');
      }
    });
  }

  $(function(){
    // Auto-load offers for any offer widget present.
    $('.pls-offer').each(function(){ loadOffers($(this)); });
    
    // Initialize all new features
    initTierCards();
    initImageGallery();
    initPriceCalculator();
    initAddToCart();
    initTabs();
    initConfiguratorModal();
    
    // Re-initialize if cards are loaded dynamically
    $(document).on('pls_tier_cards_loaded', function() {
      initTierCards();
      initPriceCalculator();
    });
  });
})(jQuery);
