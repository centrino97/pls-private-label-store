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

  $(function(){
    // Auto-load offers for any offer widget present.
    $('.pls-offer').each(function(){ loadOffers($(this)); });
    
    // Initialize tier card selection
    initTierCards();
    
    // Re-initialize if cards are loaded dynamically
    $(document).on('pls_tier_cards_loaded', function() {
      initTierCards();
    });
  });
})(jQuery);
