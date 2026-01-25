(function($){
  // Ensure ajaxurl is defined (WordPress should provide this, but add fallback)
  if (typeof window.ajaxurl === 'undefined') {
    window.ajaxurl = '/wp-admin/admin-ajax.php';
  }

  function switchTab(tab){
    $('.pls-tabs-nav .nav-tab').removeClass('nav-tab-active');
    $('.pls-tab-panel').removeClass('is-active');
    $('.pls-tabs-nav .nav-tab[data-pls-tab="'+tab+'"]').addClass('nav-tab-active');
    $('.pls-tab-panel[data-pls-tab="'+tab+'"]').addClass('is-active');
  }

  $(function(){
    // Accordion functionality
    $(document).on('click', '.pls-accordion__header', function(e){
      e.preventDefault();
      var $item = $(this).closest('.pls-accordion__item');
      $item.toggleClass('is-collapsed');
    });

    var stepOrder = ['general','data','ingredients','packs','attributes'];
    var defaultLabelGuide = 'https://bodocibiophysics.com/label-guide/';
    var currentStep = 0;

    function updateStepControls(){
      $('#pls-step-prev').prop('disabled', currentStep === 0);
      $('#pls-step-next').text(currentStep === stepOrder.length - 1 ? 'Review' : 'Next');
    }

    function goToStep(step){
      var idx = stepOrder.indexOf(step);
      if (idx === -1){ return; }
      currentStep = idx;
      $('.pls-stepper__item').removeClass('is-active');
      $('.pls-stepper__item[data-step="'+step+'"]').addClass('is-active');
      $('.pls-stepper__panel').removeClass('is-active');
      $('.pls-stepper__panel[data-step="'+step+'"]').addClass('is-active');
      updateStepControls();
    }

    $('.pls-tabs-nav').on('click', '.nav-tab', function(e){
      e.preventDefault();
      var tab = $(this).data('pls-tab');
      switchTab(tab);
    });

    $(document).on('click', '.pls-repeater-add', function(e){
      e.preventDefault();
      var templateId = $(this).data('template');
      var template = $('#' + templateId + ' .pls-repeater__row').first().clone(true);
      template.find('input').val('');
      $(this).siblings('.pls-repeater__rows').append(template);
    });

    $(document).on('click', '.pls-repeater-remove', function(e){
      e.preventDefault();
      $(this).closest('.pls-repeater__row').remove();
    });

    // Stock management toggle - show/hide stock quantity fields
    $(document).on('change', '#pls-manage-stock', function(){
      var stockFields = $('#pls-stock-fields');
      if ($(this).is(':checked')) {
        stockFields.slideDown(200);
      } else {
        stockFields.slideUp(200);
      }
    });

    var productMap = {};
    if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.products)) {
      PLS_ProductAdmin.products.forEach(function(item){
        productMap[item.id] = item;
      });
    }

    var valuePriceCache = {};
    var attrMap = {};
    if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.attributes)) {
      PLS_ProductAdmin.attributes.forEach(function(attr){
        attrMap[attr.id] = attr;
        if (Array.isArray(attr.values)){
          attr.values.forEach(function(val){
            if (typeof val.price !== 'undefined' && val.price !== ''){
              valuePriceCache[val.id] = val.price;
            }
          });
        }
      });
      
      // Debug logging for product options
      if (console && console.log) {
        console.log('[PLS Debug] Loaded ' + PLS_ProductAdmin.attributes.length + ' attributes');
        var packageTypeAttr = PLS_ProductAdmin.attributes.find(function(a) {
          return (a.attr_key === 'package-type' || 
                  (a.label && a.label.toLowerCase().indexOf('package type') !== -1));
        });
        var packageColorAttr = PLS_ProductAdmin.attributes.find(function(a) {
          return (a.attr_key === 'package-color' || a.attr_key === 'package-colour' ||
                  (a.label && a.label.toLowerCase().indexOf('package color') !== -1));
        });
        var packageCapAttr = PLS_ProductAdmin.attributes.find(function(a) {
          return (a.attr_key === 'package-cap' ||
                  (a.label && a.label.toLowerCase().indexOf('package cap') !== -1));
        });
        console.log('[PLS Debug] Package Type found:', !!packageTypeAttr, packageTypeAttr ? packageTypeAttr.label : 'N/A');
        console.log('[PLS Debug] Package Color found:', !!packageColorAttr, packageColorAttr ? packageColorAttr.label : 'N/A');
        console.log('[PLS Debug] Package Cap found:', !!packageCapAttr, packageCapAttr ? packageCapAttr.label : 'N/A');
      }
    }

      goToStep('general');

      var ingredientMap = {};
      var ingredientFilter = '';
      var ingredientSearchTimer = null;
      var ingredientRenderLimit = 100;
      var gallerySelection = [];
      if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.ingredients)) {
        PLS_ProductAdmin.ingredients.forEach(function(term){
          var key = term.term_id || term.id;
          term.id = parseInt(key, 10);
          ingredientMap[key] = term;
        });
      }
      var defaultIngredientIcon = (window.PLS_ProductAdmin && PLS_ProductAdmin.defaultIngredientIcon) || ($('#pls-ingredient-chips').data('default-icon') || '');
      var keySelected = [];
      var keyLimit = 5;

      function renderIngredientLabel(term, inputName, isChecked){
        var label = $('<label class="pls-chip-select pls-chip-select--rich"></label>');
        if (inputName){
          var input = $('<input type="checkbox" />').attr('name', inputName).val(term.id);
          if (isChecked) { input.prop('checked', true); }
          label.append(input);
        }
        var iconSrc = term.icon || defaultIngredientIcon;
        var media = $('<span class="pls-chip-media"></span>');
        if (iconSrc) {
          media.append($('<img/>').attr('src', iconSrc).attr('alt', ''));
        }
        var copy = $('<span class="pls-chip-copy"></span>');
        copy.append($('<strong></strong>').text(term.name || term.label || ('#'+term.id)));
        if (term.short_description) {
          copy.append($('<small></small>').text(term.short_description));
        }
        label.append(media).append(copy);
        return label;
      }

      function renderIngredientOption(term, isChecked){
        var label = $('<label class="pls-chip-select"></label>');
        var input = $('<input type="checkbox" />').attr('name', 'ingredient_ids[]').val(term.id);
        if (isChecked) { input.prop('checked', true); }
        label.append(input).append($('<span></span>').text(term.name || term.label || ('#'+term.id)));
        return label;
      }

      function renderSelectedIngredients(selectedIds){
        var wrap = $('#pls-selected-ingredients');
        $('#pls-selected-count').text(selectedIds.length);
        if (!wrap.length){ return; }
        wrap.empty();
        selectedIds.forEach(function(id){
          var term = ingredientMap[id] || { name: '#'+id };
          var chip = $('<span class="pls-chip"></span>').text(term.name || term.label || ('#'+id));
          wrap.append(chip);
        });
      }

      function renderIngredientList(filter){
        var wrap = $('#pls-ingredient-chips');
        if (!wrap.length) { return; }
        var preserved = wrap.find('input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        var preservedKey = $('#pls-key-ingredients input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        var normalizedFilter = (filter || '').toLowerCase();
        wrap.empty();

        var ordered = [];
        preserved.forEach(function(id){
          if (ingredientMap[id]){
            ordered.push(ingredientMap[id]);
          }
        });

        var matched = [];
        Object.values(ingredientMap).forEach(function(term){
          var normId = parseInt(term.term_id || term.id, 10);
          if (!normId || preserved.indexOf(normId) !== -1){ return; }
          var haystack = (term.name || term.label || '').toLowerCase();
          if (normalizedFilter && haystack.indexOf(normalizedFilter) === -1){ return; }
          term.id = normId;
          matched.push(term);
        });

        matched.sort(function(a,b){
          return (a.name || '').localeCompare(b.name || '');
        });

        matched.slice(0, ingredientRenderLimit).forEach(function(term){
          ordered.push(term);
        });

        ordered.forEach(function(term){
          wrap.append(renderIngredientOption(term, preserved.indexOf(term.id) !== -1));
        });

        renderSelectedIngredients(preserved);
        updateKeyIngredients(preservedKey);
      }

      function formatPrice(val){
        var num = parseFloat(val);
        if (isNaN(num)) { return ''; }
        return num.toFixed(2);
      }

      function setLabelState(enabled){
        var isEnabled = !!enabled;
        $('#pls-label-enabled').prop('checked', isEnabled);
        $('#pls-label-price, #pls-label-file').prop('disabled', !isEnabled);
        $('.pls-label-price').toggleClass('is-disabled', !isEnabled);
      }

      function renderFeaturedPreview(id, url){
        $('#pls-featured-id').val(id || '');
        var preview = $('#pls-featured-preview');
        preview.empty();
        if (!id){ return; }
        var thumb = $('<div class="pls-media-thumb"></div>');
        if (url){
          thumb.append($('<img/>').attr('src', url).attr('alt', ''));
        } else {
          thumb.text('#'+id);
        }
        var removeBtn = $('<button type="button" class="button-link-delete pls-media-thumb__remove pls-remove-featured" aria-label="Remove featured image">Remove</button>');
        thumb.append(removeBtn);
        preview.append(thumb);
      }

      function syncGalleryField(){
        var ids = gallerySelection.map(function(item){ return item.id; });
        $('#pls-gallery-ids').val(ids.join(','));
      }

      function renderGalleryPreview(){
        var wrap = $('#pls-gallery-preview');
        wrap.empty();
        gallerySelection.forEach(function(item, index){
          var cell = $('<div class="pls-media-thumb"></div>');
          if (item.url){
            cell.append($('<img/>').attr('src', item.url).attr('alt', ''));
          } else {
            cell.text('#'+item.id);
          }
          var removeBtn = $('<button type="button" class="button-link-delete pls-media-thumb__remove pls-remove-gallery" data-gallery-index="'+index+'" aria-label="Remove gallery image">×</button>');
          cell.append(removeBtn);
          wrap.append(cell);
        });
        syncGalleryField();
      }

      var keyHintDefault = $('#pls-key-ingredients-hint').text();
      var keyHintReady = $('#pls-key-ingredients-hint').data('readyText') || 'Choose which ingredients to spotlight with icons.';

      if (Object.keys(ingredientMap).length){
        renderIngredientList(ingredientFilter);
      }

      // Helper function to update tier total - must be defined before resetModal
      function updateTierTotal(row){
        var units = parseFloat(row.find('input[name*="[units]"]').val()) || 0;
        var price = parseFloat(row.find('input[name*="[price]"]').val()) || 0;
        var total = units * price;
        row.find('.pls-tier-total').text(total.toFixed(2));
      }

      function resetModal(){
        var form = $('#pls-product-form');
        if (form.length) {
          form[0].reset();
        }
        $('#pls-product-id, #pls-gallery-ids, #pls-featured-id').val('');
        gallerySelection = [];
        renderFeaturedPreview('', '');
        renderGalleryPreview();
        $('#pls-ingredient-chips input[type=checkbox], #pls-category-pills input[type=checkbox], .pls-chip-group input[type=checkbox]').prop('checked', false);
        $('#pls-benefits, #pls-long-description, #pls-short-description, #pls-directions').val('');
        ingredientFilter = '';
        $('#pls-ingredient-search').val('');
        renderIngredientList(ingredientFilter);
        $('#pls-attribute-rows').empty();
        $('#pls-label-guide').val(defaultLabelGuide);
        $('#pls-label-price').val('');
        $('#pls-label-file').prop('checked', false);
        setLabelState(true);
        $('#pls-product-errors').hide().find('ul').empty();
        
        // Reset stock management fields
        $('#pls-manage-stock').prop('checked', false);
        $('#pls-stock-quantity, #pls-low-stock-threshold').val('');
        $('#pls-stock-status').val('instock');
        $('#pls-backorders-allowed').prop('checked', false);
        $('#pls-stock-fields').hide();
        
        // Reset cost fields
        $('#pls-shipping-cost, #pls-packaging-cost').val('');
        
        goToStep('general');
        renderSelectedIngredients([]);
        keySelected = [];
        updateKeyIngredients();
        $('#pls-pack-grid .pls-pack-row').each(function(idx){
          var defaults = (window.PLS_ProductAdmin && PLS_ProductAdmin.packDefaults) ? PLS_ProductAdmin.packDefaults : [];
          var tier = defaults[idx];
          if (tier) {
            var unitVal = typeof tier === 'object' ? (tier.units || '') : tier;
            var priceVal = typeof tier === 'object' ? (tier.price || '') : '';
            $(this).find('input[name*="[units]"]').val(unitVal);
            $(this).find('input[name*="[price]"]').val(formatPrice(priceVal));
            updateTierTotal($(this));
          }
          $(this).find('input[name*="[enabled]"]').prop('checked', true);
        });
        // Reset preview mode to builder
        switchMode('builder');
      }

      function updateKeyIngredients(forceSelection){
        var wrap = $('#pls-key-ingredients');
        var hint = $('#pls-key-ingredients-hint');
        var counter = $('#pls-key-counter');
        var limitMsg = $('#pls-key-limit-message');
        var selectedIngredients = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        var preserved = Array.isArray(forceSelection) ? forceSelection.map(function(id){ return parseInt(id, 10); }) : keySelected.slice();
        var normalizedSelection = [];
        selectedIngredients.forEach(function(id){
          if (preserved.indexOf(id) !== -1 && normalizedSelection.length < keyLimit){
            normalizedSelection.push(id);
          }
        });
        keySelected = normalizedSelection;
        wrap.empty();
        limitMsg.text('');
        counter.text('Key ingredients: ' + keySelected.length + ' / ' + keyLimit);
        if (!selectedIngredients.length){
          hint.text(keyHintDefault);
          return;
        }
        hint.text(keyHintReady);
        selectedIngredients.forEach(function(id){
          var term = ingredientMap[id] || { id: id, name: '#'+id, icon: defaultIngredientIcon };
          wrap.append(renderIngredientLabel(term, 'key_ingredient_ids[]', keySelected.indexOf(id) !== -1));
        });
      }

      function getSelectedPackTierLevel(){
        // Find selected pack tier from pack grid
        var selectedTier = null;
        $('#pls-pack-grid .pls-pack-row').each(function(){
          var enabled = $(this).find('input[name*="[enabled]"]').is(':checked');
          if (enabled){
            var units = parseInt($(this).find('input[name*="[units]"]').val(), 10);
            // Map units to tier level (50=1, 100=2, 250=3, 500=4, 1000=5)
            if (units === 50) selectedTier = 1;
            else if (units === 100) selectedTier = 2;
            else if (units === 250) selectedTier = 3;
            else if (units === 500) selectedTier = 4;
            else if (units === 1000) selectedTier = 5;
          }
        });
        return selectedTier || 1; // Default to tier 1 if none selected
      }

      function populateValueOptions(attrRow){
        var attrId = parseInt(attrRow.find('.pls-attr-select').val(), 10);
        var selectEl = attrRow.find('.pls-attr-value-multi');
        selectEl.empty();
        if (!attrId || !attrMap[attrId]){
          selectEl.prop('disabled', true);
          return;
        }
        selectEl.prop('disabled', false);
        
        // Get current tier level for filtering
        var currentTier = getSelectedPackTierLevel();
        
        if (attrMap[attrId] && Array.isArray(attrMap[attrId].values)){
          attrMap[attrId].values.forEach(function(val){
            // Filter by tier: only show values where min_tier_level <= currentTier
            var minTier = val.min_tier_level || 1;
            if (minTier > currentTier){
              return; // Skip this value - not available for current tier
            }
            
            var opt = $('<option></option>').attr('value', val.id).text(val.label);
            if (typeof val.price !== 'undefined'){
              opt.attr('data-price', val.price);
              if (val.price !== ''){
                valuePriceCache[val.id] = val.price;
              }
            }
            // Store tier info for display
            if (minTier > 1){
              opt.attr('data-min-tier', minTier);
            }
            selectEl.append(opt);
          });
        }
      }

      function defaultPriceForValue(id, selectEl){
        if (id && typeof valuePriceCache[id] !== 'undefined'){
          return valuePriceCache[id];
        }
        var opt = selectEl.find('option[value="'+id+'"]');
        var optPrice = parseFloat(opt.data('price'));
        if (!isNaN(optPrice)){
          return optPrice;
        }
        return '';
      }

      function renderValueDetails(attrRow){
        var selectEl = attrRow.find('.pls-attr-value-multi');
        var details = attrRow.find('.pls-attribute-value-details');
        var customWrap = attrRow.find('.pls-attribute-custom-values');
        details.empty();
        var selected = selectEl.val() || [];
        selected.forEach(function(valId){
          var parsed = parseInt(valId, 10);
          var valObj = null;
          var attrId = parseInt(attrRow.find('.pls-attr-select').val(), 10);
          if (attrMap[attrId] && Array.isArray(attrMap[attrId].values)){
            valObj = attrMap[attrId].values.find(function(v){ return parseInt(v.id, 10) === parsed; }) || null;
          }
          var label = valObj ? valObj.label : ('#'+valId);
          var minTier = valObj && valObj.min_tier_level ? parseInt(valObj.min_tier_level, 10) : 1;
          var row = $('<div class="pls-attribute-value-detail"></div>').attr('data-value-id', valId);
          if (minTier > 1){
            row.attr('data-min-tier', minTier);
          }
          row.append('<input type="hidden" class="pls-value-id" value="'+valId+'" />');
          row.append('<input type="hidden" class="pls-value-label" value="'+label.replace(/\"/g,'&quot;')+'" />');
          var priceInput = $('<input type="number" step="0.01" class="pls-attr-price pls-price-input" inputmode="decimal" />');
          var defaultPrice = formatPrice(defaultPriceForValue(parsed, selectEl));
          if (defaultPrice){
            priceInput.attr('placeholder', defaultPrice);
            if (!priceInput.val()){
              priceInput.val(defaultPrice);
            }
          }
          var labelEl = $('<label class="pls-attribute-value-label">'+label+'</label>');
          if (minTier > 1){
            labelEl.append('<span class="pls-tier-restriction">Tier '+minTier+'+</span>');
          }
          row.append($('<div class="pls-price-inline"></div>').append(
            labelEl.append(priceInput)
          ));
          details.append(row);
        });
        renumberAttributeRows();
      }

      function addCustomValueRow(attrRow, data){
        var customWrap = attrRow.find('.pls-attribute-custom-values');
        var row = $('<div class="pls-attribute-value-custom"></div>');
        var labelInput = $('<input type="text" class="pls-attr-value-new" placeholder="Ex: Frosted 50ml" />').val(data && data.value_label ? data.value_label : '');
        var priceInput = $('<input type="number" step="0.01" class="pls-attr-price pls-price-input" inputmode="decimal" />').val(data && typeof data.price !== 'undefined' ? formatPrice(data.price) : '');
        var removeBtn = $('<button type="button" class="button-link-delete pls-attribute-custom-remove">Remove</button>');
        row.append(
          $('<div class="pls-attr-value-stack"></div>').append(
            $('<label>Custom value</label>').append(labelInput)
          )
        );
        row.append(
          $('<div class="pls-price-inline"></div>').append(
            $('<label>Price impact</label>').append(priceInput)
          )
        );
        row.append(removeBtn);
        customWrap.append(row);
        renumberAttributeRows();
      }

      function renumberAttributeRows(){
        $('#pls-attribute-rows .pls-attribute-row').each(function(idx){
          $(this).find('.pls-attr-select').attr('name', 'attr_options['+idx+'][attribute_id]');
          var vIdx = 0;
          $(this).find('.pls-attribute-value-details .pls-attribute-value-detail').each(function(){
            $(this).find('.pls-value-id').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][value_id]');
            $(this).find('.pls-value-label').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][value_label]');
            $(this).find('.pls-attr-price').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][price]');
            vIdx++;
          });
          $(this).find('.pls-attribute-custom-values .pls-attribute-value-custom').each(function(){
            $(this).find('.pls-attr-value-new').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][value_label]');
            $(this).find('.pls-attr-price').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][price]');
            vIdx++;
          });
        });
      }

      function syncAttributeRow(attrRow, forceHydrate){
        populateValueOptions(attrRow);
        if (forceHydrate){
          attrRow.find('.pls-attribute-custom-values').empty();
          attrRow.find('.pls-attr-value-multi').val([]);
        }
        if (forceHydrate){
          renderValueDetails(attrRow);
        }
      }

      function buildAttributeRow(data){
        data = data || {};
        var template = $('#pls-attribute-template .pls-attribute-row').first().clone();
        $('#pls-attribute-rows').append(template);
        renumberAttributeRows();

        if (data.attribute_id){
          template.find('.pls-attr-select').val(data.attribute_id);
        }

        populateValueOptions(template);

        var values = Array.isArray(data.values) ? data.values : [];
        if (!values.length && (data.value_id || data.value_label)){
          values = [{ value_id: data.value_id, value_label: data.value_label, price: data.price }];
        }

        var selectedIds = [];
        values.forEach(function(row){
          if (row.value_id){
            selectedIds.push(row.value_id.toString());
            if (typeof row.price !== 'undefined' && row.price !== ''){
              valuePriceCache[row.value_id] = row.price;
            }
          }
        });

        template.find('.pls-attr-value-multi').val(selectedIds);
        renderValueDetails(template);

        values.forEach(function(row){
          if (row.value_id && typeof row.price !== 'undefined'){
            template.find('.pls-attribute-value-detail[data-value-id="'+row.value_id+'"] .pls-attr-price').val(formatPrice(row.price));
          }
        });

        values.forEach(function(row){
          if (!row.value_id && row.value_label){
            addCustomValueRow(template, row);
          }
        });

        syncAttributeRow(template, false);
      }

      function addAttributeToMap(attr){
        if (!attr || !attr.id){ return; }
        attrMap[attr.id] = { id: attr.id, label: attr.label, values: attr.values || [] };
        if (Array.isArray(attr.values)){
          attr.values.forEach(function(val){
            if (typeof val.price !== 'undefined' && val.price !== ''){
              valuePriceCache[val.id] = val.price;
            }
          });
        }
        $('.pls-attr-select').each(function(){
          if (!$(this).find('option[value="'+attr.id+'"]').length){
            $(this).append('<option value="'+attr.id+'">'+attr.label+'</option>');
          }
        });
        if ($('#pls-attribute-manage-modal').hasClass('is-active')){
          renderManageAttrList();
        }
      }

      function addValueToMap(attributeId, value){
        if (!attributeId || !value){ return; }
        if (!attrMap[attributeId]){
          attrMap[attributeId] = { id: attributeId, label: '', values: [] };
        }
        if (!Array.isArray(attrMap[attributeId].values)){
          attrMap[attributeId].values = [];
        }
        attrMap[attributeId].values.push({ id: value.id, label: value.label, price: value.price });
        valuePriceCache[value.id] = typeof value.price !== 'undefined' ? value.price : valuePriceCache[value.id];
        refreshAttributeRowsForAttr(attributeId, value.id);
      }

      function refreshAttributeRowsForAttr(attributeId, preselectValueId){
        $('.pls-attribute-row').each(function(){
          var attrId = parseInt($(this).find('.pls-attr-select').val(), 10);
          if (attrId === attributeId){
            populateValueOptions($(this));
            var multi = $(this).find('.pls-attr-value-multi');
            var selected = multi.val() || [];
            if (preselectValueId && selected.indexOf(String(preselectValueId)) === -1){
              selected.push(String(preselectValueId));
            }
            multi.val(selected);
            renderValueDetails($(this));
          }
        });
      }

      function populateModal(data){
        resetModal();
        if (!data) { return; }
        $('#pls-modal-title').text('Edit product');
        $('#pls-product-id').val(data.id);
        $('#pls-name').val(data.name || '');
        $('#pls-short-description').val(data.short_description || '');
        $('#pls-long-description').val(data.long_description || '');
        $('#pls-directions').val(data.directions_text || '');
        ingredientFilter = '';
        $('#pls-ingredient-search').val('');
        renderIngredientList(ingredientFilter);

        if (Array.isArray(data.categories)){
          $('#pls-category-pills input[type=checkbox]').prop('checked', false);
          data.categories.forEach(function(id){
            $('#pls-category-pills input[value="'+id+'"]').prop('checked', true);
          });
        }

        if (Array.isArray(data.pack_tiers)){
          $('#pls-pack-grid .pls-pack-row').each(function(idx){
            var row = data.pack_tiers[idx];
            if (!row) { return; }
            $(this).find('input[name*="[units]"]').val(row.units || '');
            $(this).find('input[name*="[price]"]').val(formatPrice(row.price || ''));
            $(this).find('input[name*="[enabled]"]').prop('checked', !!parseInt(row.is_enabled || row.enabled || 0));
            updateTierTotal($(this));
          });
        }
        
        // Auto-calculate tier totals (event handler - only bind once)
        $('#pls-pack-grid').off('input', 'input[name*="[units]"], input[name*="[price]"]').on('input', 'input[name*="[units]"], input[name*="[price]"]', function(){
          updateTierTotal($(this).closest('.pls-pack-row'));
        });

        if (Array.isArray(data.skin_types)){
          data.skin_types.forEach(function(item){
            $('#pls-product-form input[name="skin_types[]"][value="'+item.label+'"]').prop('checked', true);
          });
        }

        if (Array.isArray(data.benefits)){
          var lines = data.benefits.map(function(item){ return item.label; }).join("\n");
          $('#pls-benefits').val(lines);
        }

        if (Array.isArray(data.ingredients_list)){
          data.ingredients_list.forEach(function(id){
            $('#pls-ingredient-chips input[value="'+id+'"]').prop('checked', true);
          });
        }

        var keySelections = [];
        if (Array.isArray(data.key_ingredients)){
          data.key_ingredients.forEach(function(item){
            var candidate = item.term_id || item.id;
            if (candidate) {
              keySelections.push(parseInt(candidate, 10));
            }
          });
        }
        var selectedIngredients = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        renderSelectedIngredients(selectedIngredients);
        updateKeyIngredients(keySelections);

        renderFeaturedPreview(data.featured_image_id, data.featured_image_thumb || '');
        if (Array.isArray(data.gallery_media) && data.gallery_media.length){
          gallerySelection = data.gallery_media.map(function(item){ return { id: item.id, url: item.url }; });
        } else if (Array.isArray(data.gallery_ids)){
          gallerySelection = data.gallery_ids.map(function(id){ return { id: id, url: '' }; });
        }
        renderGalleryPreview();

        setLabelState(!!parseInt(data.label_enabled));
        $('#pls-label-price').val(formatPrice(data.label_price_per_unit || ''));
        $('#pls-label-file').prop('checked', !!parseInt(data.label_requires_file));
        $('#pls-label-guide').val(data.label_guide_url || defaultLabelGuide);

        // Stock management fields
        var manageStock = !!parseInt(data.manage_stock || 0);
        $('#pls-manage-stock').prop('checked', manageStock);
        if (manageStock) {
          $('#pls-stock-fields').show();
        } else {
          $('#pls-stock-fields').hide();
        }
        $('#pls-stock-quantity').val(data.stock_quantity || '');
        $('#pls-stock-status').val(data.stock_status || 'instock');
        $('#pls-backorders-allowed').prop('checked', !!parseInt(data.backorders_allowed || 0));
        $('#pls-low-stock-threshold').val(data.low_stock_threshold || '');

        // Cost fields
        $('#pls-shipping-cost').val(data.shipping_cost || '');
        $('#pls-packaging-cost').val(data.packaging_cost || '');

        // Handle Package Type, Color, Cap separately
        if (Array.isArray(data.attributes)){
          var packageTypeAttr = null;
          var packageColorAttr = null;
          var packageCapAttr = null;
          var otherAttrs = [];
          
          data.attributes.forEach(function(row){
            var attrLabel = row.attribute_label || '';
            if (attrLabel.indexOf('Package Type') !== -1 || attrLabel.indexOf('package-type') !== -1){
              packageTypeAttr = row;
            } else if (attrLabel.indexOf('Package Color') !== -1 || attrLabel.indexOf('Package Colour') !== -1 || attrLabel.indexOf('package-color') !== -1 || attrLabel.indexOf('package-colour') !== -1){
              packageColorAttr = row;
            } else if (attrLabel.indexOf('Package Cap') !== -1 || attrLabel.indexOf('package-cap') !== -1){
              packageCapAttr = row;
            } else {
              otherAttrs.push(row);
            }
          });
          
          // Populate Package Type
          if (packageTypeAttr && packageTypeAttr.values && packageTypeAttr.values.length){
            var firstValue = packageTypeAttr.values[0];
            if (firstValue.value_id){
              $('#pls-package-type-select').val(firstValue.value_id);
            }
          }
          
          // Populate Package Colors
          if (packageColorAttr && packageColorAttr.values){
            packageColorAttr.values.forEach(function(val){
              if (val.value_id){
                $('input[name="package_colors[]"][value="'+val.value_id+'"]').prop('checked', true);
              }
            });
          }
          
          // Populate Package Caps
          if (packageCapAttr && packageCapAttr.values){
            packageCapAttr.values.forEach(function(val){
              if (val.value_id){
                $('input[name="package_caps[]"][value="'+val.value_id+'"]').prop('checked', true);
              }
            });
          }
          
          // Populate other attributes
          otherAttrs.forEach(function(row){ buildAttributeRow(row); });
        }
      }

    function updateModalState(){
      var hasActive = $('.pls-modal.is-active').length > 0;
      $('body').toggleClass('pls-modal-open', hasActive);
    }

    function closeAllModals(){
      $('.pls-modal').removeClass('is-active');
      updateModalState();
    }

    function openModalById(id){
      closeAllModals();
      $(id).addClass('is-active');
      updateModalState();
    }

    function openProductModal(data){
      resetModal();
      if (data){
        populateModal(data);
      } else {
        $('#pls-modal-title').text('Create product');
      }
      closeAllModals();
      $('#pls-product-modal').addClass('is-active');
      updateModalState();
    }

    function closeModalElement(modal){
      var target = modal.closest('.pls-modal');
      target.removeClass('is-active');
      updateModalState();
    }

    $('#pls-open-product-modal').on('click', function(){
      openProductModal(null);
    });

    // Custom product request modal
    $('#pls-open-custom-request-modal').on('click', function(e){
      e.preventDefault();
      openModalById('#pls-custom-request-modal');
    });

    $('#pls-cancel-custom-request, #pls-custom-request-modal .pls-modal__close').on('click', function(e){
      e.preventDefault();
      closeModalElement($('#pls-custom-request-modal'));
      $('#pls-custom-request-form')[0].reset();
    });

    $('#pls-custom-request-form').on('submit', function(e){
      e.preventDefault();
      var form = $(this);
      var submitBtn = form.find('button[type=submit]');
      var originalText = submitBtn.text();
      
      var productCategory = $('#pls-custom-product-category').val();
      var message = $('#pls-custom-message').val().trim();
      var contactName = $('#pls-custom-contact-name').val().trim();
      var contactEmail = $('#pls-custom-contact-email').val().trim();
      
      if (!productCategory || !message || !contactName || !contactEmail){
        alert('Please fill in all required fields.');
        return;
      }
      
      submitBtn.prop('disabled', true).text('Sending...');
      
      $.post(ajaxurl, {
        action: 'pls_custom_product_request',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        product_category: productCategory,
        message: message,
        contact_name: contactName,
        contact_email: contactEmail
      }, function(resp){
        if (resp && resp.success){
          alert(resp.data.message);
          closeModalElement($('#pls-custom-request-modal'));
          form[0].reset();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to submit request.');
        }
      }).fail(function(){
        alert('Failed to submit request. Please try again.');
      }).always(function(){
        submitBtn.prop('disabled', false).text(originalText);
      });
    });

    $('.pls-edit-product').on('click', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      openProductModal(productMap[id]);
    });

      $('.pls-modal__close').on('click', function(e){
        e.preventDefault();
        closeModalElement($(this).closest('.pls-modal'));
      });

      $('#pls-modal-cancel').on('click', function(e){
        e.preventDefault();
        closeModalElement($('#pls-product-modal'));
      });

    // Help button in product modal
    $(document).on('click', '#pls-product-modal-help', function(e){
      e.preventDefault();
      e.stopPropagation();
      
      // Check if PLS_Onboarding is available
      if (typeof PLS_Onboarding === 'undefined' || !PLS_Onboarding.ajax_url) {
        alert('Help system not available. Please refresh the page.');
        return;
      }

      // Remove existing help panel if any
      $('#pls-help-panel').remove();

      // Fetch help content for products page
      $.ajax({
        url: PLS_Onboarding.ajax_url,
        type: 'POST',
        data: {
          action: 'pls_get_helper_content',
          nonce: PLS_Onboarding.nonce,
          page: 'products'
        },
        success: function(resp) {
          if (resp && resp.success && resp.data && resp.data.content) {
            renderProductModalHelpPanel(resp.data.content);
          } else {
            alert('Unable to load help content. Please try again.');
          }
        },
        error: function() {
          alert('Error loading help content. Please refresh the page and try again.');
        }
      });
    });

    // Render help panel in modal context
    function renderProductModalHelpPanel(content) {
      var sectionsHtml = '';

      if (content.sections && content.sections.length) {
        content.sections.forEach(function(section) {
          var itemsHtml = '';
          
          if (section.items && section.items.length) {
            itemsHtml = '<ul class="pls-help-section__items">';
            section.items.forEach(function(item) {
              itemsHtml += '<li>' + item + '</li>';
            });
            itemsHtml += '</ul>';
          }

          sectionsHtml += '<div class="pls-help-section">' +
            '<h3 class="pls-help-section__title">' + section.title + '</h3>' +
            (section.content ? '<p class="pls-help-section__content">' + section.content + '</p>' : '') +
            itemsHtml +
            '</div>';
        });
      }

      var panelHtml = '<div class="pls-help-panel pls-help-panel--modal" id="pls-help-panel">' +
        '<div class="pls-help-panel__overlay"></div>' +
        '<div class="pls-help-panel__content">' +
        '<div class="pls-help-panel__header">' +
        '<h2 class="pls-help-panel__title">' + (content.title || 'Products Guide') + '</h2>' +
        '<button type="button" class="pls-help-panel__close" id="pls-help-close" aria-label="Close Help">×</button>' +
        '</div>' +
        '<div class="pls-help-panel__body">' +
        sectionsHtml +
        '</div>' +
        '</div>' +
        '</div>';

      $('body').append(panelHtml);

      // Attach close handlers
      $('#pls-help-close, .pls-help-panel__overlay').on('click', function() {
        $('#pls-help-panel').fadeOut(300, function() {
          $(this).remove();
        });
      });

      // Close on Escape key
      $(document).on('keydown.pls-help-modal', function(e) {
        if (e.key === 'Escape') {
          $('#pls-help-panel').fadeOut(300, function() {
            $(this).remove();
          });
          $(document).off('keydown.pls-help-modal');
        }
      });

      // Scroll to top of panel
      $('.pls-help-panel__body').scrollTop(0);
    }

    $(document).on('click', '.pls-delete-product', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      if (!id){ return; }
      if (!confirm('Delete this product and trash its WooCommerce product?')){
        return;
      }
      var button = $(this);
      button.prop('disabled', true);
      $.post(ajaxurl, { action: 'pls_delete_product', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), id: id }, function(resp){
        if (resp && resp.success){
          $('.pls-card[data-product-id="'+id+'"]').remove();
          delete productMap[id];
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not delete product.');
        }
      }).fail(function(){
        alert('Could not delete product.');
      }).always(function(){
        button.prop('disabled', false);
      });
    });

    $(document).on('click', '.pls-sync-product, .pls-update-product', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      if (!id){ return; }
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Syncing...');
      $.post(ajaxurl, { action: 'pls_sync_product', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), id: id }, function(resp){
        if (resp && resp.success && resp.data && resp.data.product){
          productMap[id] = resp.data.product;
          // Reload page to show updated sync state and buttons
          window.location.reload();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not sync product.');
          button.prop('disabled', false).text(originalText);
        }
      }).fail(function(){
        alert('Could not sync product.');
        button.prop('disabled', false).text(originalText);
      });
    });

    $(document).on('click', '.pls-activate-product', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      if (!id){ return; }
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Activating...');
      $.post(ajaxurl, { action: 'pls_activate_product', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), id: id }, function(resp){
        if (resp && resp.success && resp.data && resp.data.product){
          productMap[id] = resp.data.product;
          // Reload page to show updated sync state and buttons
          window.location.reload();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not activate product.');
          button.prop('disabled', false).text(originalText);
        }
      }).fail(function(){
        alert('Could not activate product.');
        button.prop('disabled', false).text(originalText);
      });
    });

    $(document).on('click', '.pls-deactivate-product', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      if (!id){ return; }
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Deactivating...');
      $.post(ajaxurl, { action: 'pls_deactivate_product', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), id: id }, function(resp){
        if (resp && resp.success && resp.data && resp.data.product){
          productMap[id] = resp.data.product;
          // Reload page to show updated sync state and buttons
          window.location.reload();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not deactivate product.');
          button.prop('disabled', false).text(originalText);
        }
      }).fail(function(){
        alert('Could not deactivate product.');
        button.prop('disabled', false).text(originalText);
      });
    });

    $('#pls-sync-all').on('click', function(e){
      e.preventDefault();
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Syncing...');
      $.post(ajaxurl, { action: 'pls_sync_all_products', nonce: (window.PLS_Admin ? PLS_Admin.nonce : '') }, function(resp){
        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Sync complete.');
        window.location.reload();
      }).fail(function(){
        alert('Could not sync all products.');
      }).always(function(){
        button.prop('disabled', false).text(originalText);
      });
    });

    $('#pls-open-ingredient-create').on('click', function(e){
      e.preventDefault();
      openModalById('#pls-ingredient-create-modal');
    });

    $('#pls-cancel-ingredient-create, #pls-ingredient-create-modal .pls-modal__close').on('click', function(e){
      e.preventDefault();
      closeModalElement($('#pls-ingredient-create-modal'));
    });

    $('#pls-create-ingredient-form').on('submit', function(e){
      e.preventDefault();
      var name = ($('#pls-new-ingredient-name').val() || '').trim();
      if (!name){
        alert('Ingredient name is required.');
        return;
      }
      var payload = {
        action: 'pls_create_ingredients',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        ingredients: [{
          name: name,
          short_description: ($('#pls-new-ingredient-short').val() || '').trim(),
          icon_id: parseInt($('#pls-new-ingredient-icon').val(), 10) || 0
        }]
      };
      var submitBtn = $('#pls-save-ingredient-create');
      var originalText = submitBtn.text();
      submitBtn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, payload, function(resp){
        if (!resp || !resp.success || !resp.data || !Array.isArray(resp.data.ingredients)){
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create ingredient.');
          return;
        }
        if (resp.data.default_icon){
          defaultIngredientIcon = resp.data.default_icon;
        }
        resp.data.ingredients.forEach(function(term){
          var key = term.term_id || term.id;
          term.id = parseInt(key, 10);
          ingredientMap[key] = term;
        });
        renderIngredientList(ingredientFilter);
        closeModalElement($('#pls-ingredient-create-modal'));
        $('#pls-new-ingredient-name').val('');
        $('#pls-new-ingredient-short').val('');
        $('#pls-new-ingredient-icon').val('');
        $('#pls-new-ingredient-icon-preview').empty();
        var newId = resp.data.ingredients.length ? (resp.data.ingredients[0].id || resp.data.ingredients[0].term_id) : 0;
        if (newId){
          $('#pls-ingredient-chips input[value="'+newId+'"]').prop('checked', true);
          var selected = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
          renderSelectedIngredients(selected);
          updateKeyIngredients();
        }
      }).fail(function(){
        alert('Could not create ingredient.');
      }).always(function(){
        submitBtn.prop('disabled', false).text(originalText);
      });
    });

    var manageAttrCurrent = 0;

      function renderManageAttrList(){
        var list = $('#pls-manage-attr-list');
        list.empty();
        var firstId = 0;
        Object.values(attrMap).forEach(function(attr){
          if (!firstId){ firstId = attr.id; }
          var btn = $('<button type="button" class="button button-small pls-manage-attr-item"></button>')
            .attr('data-attr-id', attr.id)
            .text(attr.label);
          if (attr.id === manageAttrCurrent){
            btn.addClass('is-active');
          }
          list.append(btn);
        });
        if (!manageAttrCurrent && firstId){
          manageAttrCurrent = firstId;
        }
        if (manageAttrCurrent){
          selectManageAttribute(manageAttrCurrent);
        } else {
          $('#pls-manage-attr-current').text('');
          $('#pls-manage-value-list').empty();
        }
      }

    function selectManageAttribute(attrId){
      manageAttrCurrent = parseInt(attrId, 10) || 0;
      $('#pls-manage-attr-list .pls-manage-attr-item').removeClass('is-active');
      $('#pls-manage-attr-list .pls-manage-attr-item[data-attr-id="'+manageAttrCurrent+'"]').addClass('is-active');
      var attr = attrMap[manageAttrCurrent];
      if (!attr){
        $('#pls-manage-attr-current').text('');
        $('#pls-manage-value-list').empty();
        return;
      }
      $('#pls-manage-attr-current').text(attr.label);
      $('#pls-manage-attr-hint').text('');
      var list = $('#pls-manage-value-list');
      list.empty();
      (attr.values || []).forEach(function(val){
        var row = $('<div class="pls-manage-value-row"></div>').attr('data-value-id', val.id);
        row.append($('<span class="pls-manage-value-label"></span>').text(val.label));
        var priceInput = $('<input type="number" step="0.01" class="pls-price-input pls-manage-value-price" />').val(val.price !== undefined && val.price !== '' ? formatPrice(val.price) : '');
        priceInput.attr('placeholder', formatPrice(val.price));
        row.append(priceInput);
        list.append(row);
      });
    }

    $('#pls-open-attribute-manage').on('click', function(e){
      e.preventDefault();
      openModalById('#pls-attribute-manage-modal');
      renderManageAttrList();
    });

    $(document).on('click', '.pls-manage-attr-item', function(){
      var id = parseInt($(this).data('attr-id'), 10);
      selectManageAttribute(id);
    });

    $('#pls-manage-attr-create').on('submit', function(e){
      e.preventDefault();
      var label = ($('#pls-manage-attr-label').val() || '').trim();
      if (!label){ return; }
      var payload = {
        action: 'pls_create_attribute',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        label: label,
        is_variation: $('#pls-manage-attr-variation').is(':checked') ? 1 : 0
      };
      var btn = $(this).find('button[type=submit]');
      var original = btn.text();
      btn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, payload, function(resp){
        if (resp && resp.success && resp.data && resp.data.attribute){
          addAttributeToMap(resp.data.attribute);
          $('#pls-manage-attr-label').val('');
          $('#pls-manage-attr-variation').prop('checked', true);
          manageAttrCurrent = resp.data.attribute.id;
          renderManageAttrList();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create attribute.');
        }
      }).fail(function(){
        alert('Could not create attribute.');
      }).always(function(){
        btn.prop('disabled', false).text(original);
      });
    });

    $('#pls-manage-value-create').on('submit', function(e){
      e.preventDefault();
      var label = ($('#pls-manage-value-label').val() || '').trim();
      var price = $('#pls-manage-value-price').val();
      if (!manageAttrCurrent || !label){ return; }
      var payload = {
        action: 'pls_create_attribute_value',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        attribute_id: manageAttrCurrent,
        label: label,
        price: price
      };
      var btn = $(this).find('button[type=submit]');
      var original = btn.text();
      btn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, payload, function(resp){
        if (resp && resp.success && resp.data && resp.data.value){
          addValueToMap(manageAttrCurrent, resp.data.value);
          $('#pls-manage-value-label').val('');
          $('#pls-manage-value-price').val('');
          selectManageAttribute(manageAttrCurrent);
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create value.');
        }
      }).fail(function(){
        alert('Could not create value.');
      }).always(function(){
        btn.prop('disabled', false).text(original);
      });
    });

    $('#pls-manage-save-values').on('click', function(e){
      e.preventDefault();
      if (!manageAttrCurrent){ return; }
      var rows = $('#pls-manage-value-list .pls-manage-value-row');
      var values = [];
      rows.each(function(){
        var id = parseInt($(this).data('value-id'), 10);
        var price = $(this).find('.pls-manage-value-price').val();
        values.push({ id: id, price: price });
      });
      var btn = $(this);
      var original = btn.text();
      btn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, {
        action: 'pls_update_attribute_values',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        attribute_id: manageAttrCurrent,
        values: values
      }, function(resp){
        if (resp && resp.success && resp.data && resp.data.attribute){
          addAttributeToMap(resp.data.attribute);
          selectManageAttribute(manageAttrCurrent);
          refreshAttributeRowsForAttr(manageAttrCurrent);
          $('#pls-manage-value-status').text('Defaults saved.');
          setTimeout(function(){ $('#pls-manage-value-status').text(''); }, 2000);
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not save values.');
        }
      }).fail(function(){
        alert('Could not save values.');
      }).always(function(){
        btn.prop('disabled', false).text(original);
      });
    });

    $('#pls-stepper-nav').on('click', '.pls-stepper__item', function(){
      var step = $(this).data('step');
      goToStep(step);
    });

    $('#pls-step-next').on('click', function(){
      if (currentStep < stepOrder.length - 1){
        goToStep(stepOrder[currentStep + 1]);
      }
    });

    $('#pls-step-prev').on('click', function(){
      if (currentStep > 0){
        goToStep(stepOrder[currentStep - 1]);
      }
    });

    $('#pls-label-enabled').on('change', function(){
      setLabelState($(this).is(':checked'));
    });

      $('#pls-add-attribute-row').on('click', function(e){
        e.preventDefault();
        buildAttributeRow();
      });

      $(document).on('change', '.pls-attr-select', function(){
        var row = $(this).closest('.pls-attribute-row');
        syncAttributeRow(row, true);
        renumberAttributeRows();
      });

      $(document).on('change', '.pls-attr-value-multi', function(){
        var row = $(this).closest('.pls-attribute-row');
        renderValueDetails(row);
      });

      $(document).on('click', '.pls-attribute-remove', function(e){
        e.preventDefault();
        $(this).closest('.pls-attribute-row').remove();
        renumberAttributeRows();
      });

      $(document).on('click', '.pls-attribute-value-add-custom', function(e){
        e.preventDefault();
        var attrRow = $(this).closest('.pls-attribute-row');
        addCustomValueRow(attrRow, {});
      });

      $(document).on('click', '.pls-attribute-custom-remove', function(e){
        e.preventDefault();
        $(this).closest('.pls-attribute-value-custom').remove();
        renumberAttributeRows();
      });

      $(document).on('change blur', '.pls-attr-price', function(){
        var row = $(this).closest('.pls-attribute-value-detail');
        var valId = parseInt(row.data('value-id'), 10);
        var priceVal = parseFloat($(this).val());
        if (valId && !isNaN(priceVal)){
          valuePriceCache[valId] = priceVal;
        }
      });

      $('#pls-ingredient-chips').on('change', 'input[type=checkbox]', function(){
        var selected = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        renderSelectedIngredients(selected);
        updateKeyIngredients();
      });

      $('#pls-key-ingredients').on('change', 'input[type=checkbox]', function(){
        var checked = $('#pls-key-ingredients input:checked');
        if (checked.length > keyLimit){
          $(this).prop('checked', false);
          $('#pls-key-limit-message').text('Choose up to '+keyLimit+' key ingredients.');
        }
        keySelected = $('#pls-key-ingredients input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        $('#pls-key-counter').text('Key ingredients: ' + keySelected.length + ' / ' + keyLimit);
        if (keySelected.length <= keyLimit){
          $('#pls-key-limit-message').text('');
        }
      });

      $('#pls-ingredient-search').on('input', function(){
        var value = ($(this).val() || '').toLowerCase();
        clearTimeout(ingredientSearchTimer);
        ingredientSearchTimer = setTimeout(function(){
          ingredientFilter = value;
          renderIngredientList(ingredientFilter);
        }, 300);
      });

      $(document).on('input', '.pls-price-input', function(){
        var cleaned = $(this).val().replace(/[^0-9.]/g, '');
        var parts = cleaned.split('.');
        if (parts.length > 2){
          cleaned = parts.shift() + '.' + parts.join('');
        }
        $(this).val(cleaned);
      });

      $(document).on('blur', '.pls-price-input', function(){
        var formatted = formatPrice($(this).val());
        if (formatted){
          $(this).val(formatted);
        }
      });

      function renderErrors(errors){
        var box = $('#pls-product-errors');
        var list = box.find('ul');
        list.empty();
        errors.forEach(function(msg){ list.append('<li>'+msg+'</li>'); });
        if (errors.length){
          box.show();
          $('html, body').animate({ scrollTop: box.offset().top - 40 }, 200);
        } else {
          box.hide();
        }
      }

      function validateProductForm(){
        var errors = [];
        if (!$('#pls-name').val().trim()){
          errors.push('Name is required.');
        }
        if (!$('#pls-category-pills input:checked').length){
          errors.push('Select at least one category.');
        }
        if (!$('#pls-featured-id').val()){
          errors.push('Featured image is required.');
        }
        if (!$('#pls-gallery-ids').val()){
          errors.push('Add at least one gallery image.');
        }
        if (!$('#pls-short-description').val().trim()){
          errors.push('Short description is required.');
        }
        if (!$('#pls-long-description').val().trim()){
          errors.push('Long description is required.');
        }
        if (!$('#pls-directions').val().trim()){
          errors.push('Directions are required.');
        }
        if (!$('input[name="skin_types[]"]:checked').length){
          errors.push('Select at least one skin type.');
        }
        var benefitsText = $('#pls-benefits').val() || '';
        if (!benefitsText.trim()){
          errors.push('Provide at least one benefit.');
        }
        if (!$('#pls-ingredient-chips input:checked').length){
          errors.push('Select at least one ingredient.');
        }
        var enabledTier = false;
        $('#pls-pack-grid .pls-pack-row').each(function(){
          var units = parseInt($(this).find('input[name*="[units]"]').val(), 10) || 0;
          var priceVal = parseFloat($(this).find('input[name*="[price]"]').val());
          var enabled = $(this).find('input[name*="[enabled]"]').is(':checked');
          if (enabled){ enabledTier = true; }
          if (enabled && (isNaN(priceVal) || priceVal <= 0)){
            errors.push('Pack tier prices must be greater than zero.');
            return false;
          }
          if (enabled && units <= 0){
            errors.push('Pack tier units must be provided.');
            return false;
          }
        });
        if (!enabledTier){
          errors.push('Enable at least one pack tier.');
        }

        $('#pls-attribute-rows .pls-attribute-row').each(function(){
          var attrSelectVal = $(this).find('.pls-attr-select').val();
          if (!attrSelectVal){
            return;
          }
          var valueRows = $(this).find('.pls-attribute-value-detail, .pls-attribute-value-custom');
          if (!valueRows.length){
            errors.push('Add at least one value for each attribute.');
            return;
          }
          valueRows.each(function(){
            var selectVal = $(this).find('.pls-value-id').val();
            var newLabel = ($(this).find('.pls-attr-value-new').val() || '').trim() || ($(this).find('.pls-value-label').val() || '').trim();
            if (!selectVal && !newLabel){
              errors.push('Attribute values need a selection or custom label.');
              return false;
            }
            var priceVal = $(this).find('.pls-attr-price').val();
            if (priceVal && isNaN(parseFloat(priceVal))){
              errors.push('Price impacts must be numeric.');
              return false;
            }
            return true;
          });
        });

        if ($('#pls-label-enabled').is(':checked')) {
          var labelPrice = parseFloat($('#pls-label-price').val());
          if (isNaN(labelPrice) || labelPrice <= 0){
            errors.push('Label price per unit must be greater than zero when label application is enabled.');
          }
        }

        $('.pls-price-input').each(function(){
          var val = $(this).val();
          if (val && isNaN(parseFloat(val))){
            errors.push('Price fields must use numbers only.');
            return false;
          }
          return true;
        });

        return errors;
      }

    function pickImage(callback, multiple){
      if (typeof wp === 'undefined' || typeof wp.media !== 'function') {
        console.error('wp.media is not available.');
        return;
      }
      var frame = wp.media({
        title: 'Select media',
        multiple: !!multiple
      });
      frame.on('select', function(){
        var selection = frame.state().get('selection');
        callback(selection.toJSON());
      });
      frame.open();
    }

    $('#pls-pick-featured').on('click', function(e){
      e.preventDefault();
      pickImage(function(files){
        if (!files || !files.length){ return; }
        var file = files[0];
        var thumbUrl = (file.sizes && file.sizes.thumbnail && file.sizes.thumbnail.url) ? file.sizes.thumbnail.url : file.url;
        renderFeaturedPreview(file.id, thumbUrl);
      }, false);
    });

    $('#pls-pick-gallery').on('click', function(e){
      e.preventDefault();
      pickImage(function(files){
        gallerySelection = files.map(function(file){
          var thumbUrl = (file.sizes && file.sizes.thumbnail && file.sizes.thumbnail.url) ? file.sizes.thumbnail.url : file.url;
          return { id: file.id, url: thumbUrl };
        });
        renderGalleryPreview();
      }, true);
    });

    $(document).on('click', '.pls-remove-featured', function(e){
      e.preventDefault();
      renderFeaturedPreview('', '');
    });

    $(document).on('click', '.pls-remove-gallery', function(e){
      e.preventDefault();
      var idx = parseInt($(this).data('gallery-index'), 10);
      if (!isNaN(idx)){
        gallerySelection.splice(idx, 1);
        renderGalleryPreview();
      }
    });

    function renderServerErrors(resp){
      if (resp && resp.responseJSON && resp !== resp.responseJSON){
        renderServerErrors(resp.responseJSON);
        return;
      }
      var errs = [];
      if (resp && resp.data && Array.isArray(resp.data.errors)){
        resp.data.errors.forEach(function(item){
          if (item && item.message){
            errs.push(item.message);
          }
        });
      } else if (resp && resp.data && resp.data.message){
        errs.push(resp.data.message);
      } else if (resp && resp.message){
        errs.push(resp.message);
      }
      if (!errs.length){
        errs.push('Could not save product.');
      }
      renderErrors(errs);
    }

    function formatSyncStatus(status){
      if (!status){
        return 'Not synced yet.';
      }
      var label = status.success ? 'Synced' : 'Sync failed';
      var parts = [label];
      if (status.timestamp){
        parts.push(new Date(status.timestamp * 1000).toLocaleString());
      }
      if (status.message){
        parts.push(status.message);
      }
      return parts.join(' – ');
    }

    function updateCardSync(product){
      var card = $('.pls-card[data-product-id="'+product.id+'"]');
      var meta = card.find('.pls-sync-meta');
      if (meta.length){
        meta.text(formatSyncStatus(product.sync_status));
      }
    }

    // Refresh attribute options when pack tier changes
    $(document).on('change', '#pls-pack-grid input[name*="[enabled]"], #pls-pack-grid input[name*="[units]"]', function(){
      // Refresh all attribute rows to filter by new tier level
      $('.pls-attribute-row').each(function(){
        var attrRow = $(this);
        var currentSelected = attrRow.find('.pls-attr-value-multi').val() || [];
        populateValueOptions(attrRow);
        // Try to preserve selected values if they're still available
        var availableOptions = attrRow.find('.pls-attr-value-multi option').map(function(){
          return $(this).val();
        }).get();
        var preserved = currentSelected.filter(function(id){
          return availableOptions.indexOf(id) !== -1;
        });
        attrRow.find('.pls-attr-value-multi').val(preserved);
        renderValueDetails(attrRow);
      });
      
      // Refresh preview if in preview mode
      if ($('#pls-preview-panel').is(':visible')){
        clearTimeout(previewDebounce);
        previewDebounce = setTimeout(generatePreview, 500);
      }
    });

      $('#pls-product-form').on('submit', function(e){
        e.preventDefault();
        var errors = validateProductForm();
        if (errors.length){
          renderErrors(errors);
          return;
        }
        renderErrors([]);
        var form = $(this);
        var submitBtn = form.find('button[type=submit]');
        var originalText = submitBtn.text();
        var payload = form.serializeArray();
        payload.push({ name: 'action', value: 'pls_save_product' });
        payload.push({ name: 'nonce', value: (window.PLS_Admin ? PLS_Admin.nonce : '') });
        submitBtn.prop('disabled', true).text('Saving...');
        $.post(ajaxurl, payload, function(resp){
          if (!resp || !resp.success || !resp.data || resp.data.ok === false){
            renderServerErrors(resp);
            return;
          }
          if (resp.data && resp.data.product){
            productMap[resp.data.product.id] = resp.data.product;
          }
          closeModalElement($('#pls-product-modal'));
          window.location.reload();
        }).fail(function(resp){
          renderServerErrors(resp);
        }).always(function(){
          submitBtn.prop('disabled', false).text(originalText);
        });
      });

      // Mode toggle handler
      $('.pls-mode-btn').on('click', function(){
        var mode = $(this).data('mode');
        switchMode(mode);
      });

      function switchMode(mode){
        $('.pls-mode-btn').removeClass('is-active');
        $('.pls-mode-btn[data-mode="'+mode+'"]').addClass('is-active');
        
        if (mode === 'preview'){
          $('.pls-stepper, .pls-modal__footer .pls-stepper__controls, .pls-modal__footer .pls-stepper__actions').hide();
          $('#pls-preview-panel').show();
          // Make modal fullscreen for preview
          $('#pls-product-modal').addClass('pls-modal-fullscreen');
          generatePreview();
        } else {
          $('.pls-stepper, .pls-modal__footer .pls-stepper__controls, .pls-modal__footer .pls-stepper__actions').show();
          $('#pls-preview-panel').hide();
          $('#pls-product-modal').removeClass('pls-modal-fullscreen');
        }
      }

      // Preview mode toggle (split/fullscreen)
      $(document).on('click', '.pls-preview-mode-btn', function(){
        var mode = $(this).data('mode');
        $('.pls-preview-mode-btn').removeClass('active');
        $(this).addClass('active');
        
        if (mode === 'split'){
          $('#pls-product-modal').removeClass('pls-modal-fullscreen').addClass('pls-modal-split');
          $('#pls-product-modal .pls-modal__dialog').css({
            'display': 'flex',
            'max-width': '100%',
            'width': '100%',
            'height': '100vh'
          });
          $('#pls-product-form').css('width', '50%');
          $('#pls-preview-panel').css('width', '50%');
        } else {
          $('#pls-product-modal').removeClass('pls-modal-split').addClass('pls-modal-fullscreen');
          $('#pls-product-modal .pls-modal__dialog').css({
            'display': 'block',
            'max-width': '100%',
            'width': '100%',
            'height': '100vh'
          });
          $('#pls-product-form').css('width', '100%');
          $('#pls-preview-panel').css('width', '100%');
        }
      });

      var previewDebounce = null;
      function generatePreview(){
        var formData = $('#pls-product-form').serializeArray();
        var productId = $('#pls-product-id').val();
        var wcProductId = null;
        
        // Try to get WC product ID from product map
        if (productId && productMap[productId] && productMap[productId].wc_product_id){
          wcProductId = productMap[productId].wc_product_id;
        }
        
        // If product is synced, use actual WooCommerce product URL
        if (wcProductId){
          var previewUrl = '<?php echo esc_js( home_url() ); ?>?product_id=' + wcProductId + '&pls_preview=1&pls_preview_nonce=' + (window.PLS_Admin ? PLS_Admin.nonce : '');
          $('#pls-preview-iframe').attr('src', previewUrl);
          $('.pls-preview-loading').hide();
          $('#pls-preview-iframe').show();
          return;
        }
        
        // Otherwise generate preview HTML from form data
        formData.push({ name: 'action', value: 'pls_preview_product' });
        formData.push({ name: 'nonce', value: (window.PLS_Admin ? PLS_Admin.nonce : '') });
        
        $('#pls-preview-iframe').hide();
        $('.pls-preview-loading').show();
        
        $.post(ajaxurl, formData, function(resp){
          if (resp && resp.success && resp.data && resp.data.html){
            var iframe = document.getElementById('pls-preview-iframe');
            if (iframe && iframe.contentDocument){
              iframe.contentDocument.open();
              iframe.contentDocument.write(resp.data.html);
              iframe.contentDocument.close();
              $('.pls-preview-loading').hide();
              $('#pls-preview-iframe').show();
            }
          } else {
            $('.pls-preview-loading').html('<p style="color: #d63638;">Preview generation failed. Please check form data.</p>');
          }
        }).fail(function(){
          $('.pls-preview-loading').html('<p style="color: #d63638;">Failed to generate preview. Please try again.</p>');
        });
      }

      // Auto-refresh preview on field changes (debounced)
      $(document).on('input change', '#pls-product-form input, #pls-product-form textarea, #pls-product-form select', function(){
        if ($('#pls-preview-panel').is(':visible')){
          clearTimeout(previewDebounce);
          previewDebounce = setTimeout(generatePreview, 800);
        }
        // Update price calculator
        updatePriceCalculator();
      });

      // Inline validation
      function validateField(field){
        var fieldName = field.attr('name') || field.attr('id');
        var value = field.val();
        var errorMsg = '';
        
        // Remove existing error
        field.removeClass('pls-field-error');
        field.next('.pls-field-error-msg').remove();
        
        // Field-specific validation
        if (fieldName === 'name' || fieldName === 'pls-name'){
          if (!value || value.trim().length < 3){
            errorMsg = 'Product name must be at least 3 characters';
          }
        }
        
        if (fieldName === 'package_type_attr' || fieldName === 'pls-package-type-select'){
          if (!value){
            errorMsg = 'Please select a package type';
          }
        }
        
        if (fieldName && fieldName.indexOf('pack_tiers') !== -1 && fieldName.indexOf('[price]') !== -1){
          var priceVal = parseFloat(value);
          if (isNaN(priceVal) || priceVal <= 0){
            errorMsg = 'Price must be greater than zero';
          }
        }
        
        // Show error if found
        if (errorMsg){
          field.addClass('pls-field-error');
          field.after('<span class="pls-field-error-msg" style="display:block;color:#d63638;font-size:12px;margin-top:4px;">' + errorMsg + '</span>');
        }
        
        return !errorMsg;
      }

      // Real-time field validation
      $(document).on('blur', '#pls-name, #pls-package-type-select, input[name*="[price]"]', function(){
        validateField($(this));
      });

      // Price calculator
      function updatePriceCalculator(){
        var selectedTier = parseInt($('#pls-calc-tier-select').val(), 10) || 1;
        var basePrice = 0;
        var addons = [];
        
        // Get base price from selected pack tier
        $('#pls-pack-grid .pls-pack-row').each(function(){
          var enabled = $(this).find('input[name*="[enabled]"]').is(':checked');
          if (enabled){
            var units = parseInt($(this).find('input[name*="[units]"]').val(), 10) || 0;
            var pricePerUnit = parseFloat($(this).find('input[name*="[price]"]').val()) || 0;
            
            // Map units to tier level
            var tierLevel = 1;
            if (units <= 50) tierLevel = 1;
            else if (units <= 100) tierLevel = 2;
            else if (units <= 250) tierLevel = 3;
            else if (units <= 500) tierLevel = 4;
            else tierLevel = 5;
            
            if (tierLevel === selectedTier){
              basePrice = units * pricePerUnit;
            }
          }
        });
        
        // Calculate addons from package color
        $('input[name="package_colors[]"]:checked').each(function(){
          var badge = $(this).closest('.pls-option-card').find('.pls-price-badge');
          var tierPrices = badge.data('tier-prices');
          var defaultPrice = badge.data('default-price') || 0;
          var price = 0;
          
          if (tierPrices && typeof tierPrices === 'object' && tierPrices[selectedTier]){
            price = parseFloat(tierPrices[selectedTier]);
          } else if (defaultPrice > 0){
            price = defaultPrice;
          }
          
          if (price > 0){
            var label = $(this).closest('.pls-option-card').find('strong').text();
            addons.push({ label: label, price: price });
          }
        });
        
        // Calculate addons from package cap
        $('input[name="package_caps[]"]:checked').each(function(){
          var badge = $(this).closest('.pls-option-card').find('.pls-price-badge');
          var tierPrices = badge.data('tier-prices');
          var defaultPrice = badge.data('default-price') || 0;
          var price = 0;
          
          if (tierPrices && typeof tierPrices === 'object' && tierPrices[selectedTier]){
            price = parseFloat(tierPrices[selectedTier]);
          } else if (defaultPrice > 0){
            price = defaultPrice;
          }
          
          if (price > 0){
            var label = $(this).closest('.pls-option-card').find('strong').text();
            addons.push({ label: label, price: price });
          }
        });
        
        // Label application fee
        if ($('#pls-label-enabled').is(':checked')){
          var labelPrice = 0;
          if (selectedTier >= 3){
            labelPrice = 0; // FREE for Tier 3+
          } else {
            labelPrice = parseFloat($('#pls-label-price').val()) || 0;
            if (labelPrice > 0){
              // Multiply by units for selected tier
              var tierUnits = 50;
              if (selectedTier === 2) tierUnits = 100;
              else if (selectedTier === 3) tierUnits = 250;
              else if (selectedTier === 4) tierUnits = 500;
              else if (selectedTier === 5) tierUnits = 1000;
              labelPrice = labelPrice * tierUnits;
            }
          }
          if (labelPrice > 0){
            addons.push({ label: 'Label Application', price: labelPrice });
          }
        }
        
        // Update calculator display
        var unitsLabel = '50';
        if (selectedTier === 2) unitsLabel = '100';
        else if (selectedTier === 3) unitsLabel = '250';
        else if (selectedTier === 4) unitsLabel = '500';
        else if (selectedTier === 5) unitsLabel = '1000';
        
        $('#pls-calc-base-label').text('Base Price (' + unitsLabel + ' units)');
        $('#pls-calc-base-price').text('$' + basePrice.toFixed(2));
        
        var addonsHtml = '';
        var totalAddons = 0;
        addons.forEach(function(addon){
          totalAddons += addon.price;
          addonsHtml += '<div class="pls-calc-row pls-calc-addon" style="display:flex;justify-content:space-between;padding:6px 0;color:#64748b;font-size:14px;">';
          addonsHtml += '<span>+ ' + addon.label + '</span>';
          addonsHtml += '<strong>+$' + addon.price.toFixed(2) + '</strong>';
          addonsHtml += '</div>';
        });
        
        $('#pls-calc-addons').html(addonsHtml);
        var total = basePrice + totalAddons;
        $('#pls-calc-total-price').text('$' + total.toFixed(2));
      }

      // Update price calculator when tier selector changes
      $('#pls-calc-tier-select').on('change', updatePriceCalculator);
      
      // Update price badges based on selected tier
      function updatePriceBadges(){
        var selectedTier = parseInt($('#pls-calc-tier-select').val(), 10) || 1;
        
        $('.pls-price-badge[data-tier-prices]').each(function(){
          var badge = $(this);
          var tierPrices = badge.data('tier-prices');
          if (tierPrices && typeof tierPrices === 'object' && tierPrices[selectedTier]){
            var price = parseFloat(tierPrices[selectedTier]);
            var currentText = badge.text();
            var newText = currentText.replace(/\+?\$[\d.]+.*/, '+$' + price.toFixed(2) + ' (Tier ' + selectedTier + ')');
            badge.text(newText);
          }
        });
      }
      
      $('#pls-calc-tier-select').on('change', updatePriceBadges);

      // Cap compatibility matrix (updates based on package type)
      function updateCapCompatibility(){
        var packageType = $('#pls-package-type-select option:selected').text().toLowerCase();
        var isJar = packageType.indexOf('jar') !== -1;
        var isLargeBottle = packageType.indexOf('120ml') !== -1;
        
        $('input[name="package_caps[]"]').each(function(){
          var capLabel = $(this).closest('.pls-option-card').find('strong').text().toLowerCase();
          var isDropper = capLabel.indexOf('dropper') !== -1;
          var isPump = capLabel.indexOf('pump') !== -1;
          var isLid = capLabel.indexOf('lid') !== -1;
          
          var shouldDisable = false;
          if (isJar && !isLid){
            shouldDisable = true; // Jar only supports lid
          } else if (isLargeBottle && isDropper){
            shouldDisable = true; // 120ml doesn't support dropper
          }
          
          $(this).prop('disabled', shouldDisable);
          if (shouldDisable && $(this).is(':checked')){
            $(this).prop('checked', false);
          }
          $(this).closest('.pls-option-card').toggleClass('pls-option-disabled', shouldDisable);
        });
      }
      
      $('#pls-package-type-select').on('change', updateCapCompatibility);

    $(document).on('click', '.pls-icon-pick', function(e){
      e.preventDefault();
      var picker = $(this).closest('.pls-icon-picker');
      var target = picker.data('target');
      pickImage(function(files){
        if (!files || !files.length){ return; }
        var file = files[0];
        var input = picker.find('input[type=hidden]');
        input.val(file.id);
        picker.closest('tr, .pls-icon-picker').find('.pls-icon-preview').html('<img src="'+file.url+'" style="max-height:32px;" />');
      }, false);
    });

    $(document).on('click', '.pls-icon-clear', function(e){
      e.preventDefault();
      var picker = $(this).closest('.pls-icon-picker');
      picker.find('input[type=hidden]').val('');
      picker.closest('tr, .pls-icon-picker').find('.pls-icon-preview').empty();
    });

    // Bundle Modal Functionality
    function openBundleModal(bundleData) {
      if (bundleData) {
        $('#pls-bundle-modal-title').text('Edit Bundle');
        $('#pls-bundle-id').val(bundleData.id);
        $('#bundle_name').val(bundleData.name);
        $('#bundle_type').val(bundleData.bundle_type);
        $('#sku_count').val(bundleData.sku_count);
        $('#units_per_sku').val(bundleData.units_per_sku);
        $('#price_per_unit').val(bundleData.price_per_unit);
        $('#commission_per_unit').val(bundleData.commission_per_unit);
        $('#bundle_status').val(bundleData.status);
      } else {
        $('#pls-bundle-modal-title').text('Create Bundle');
        $('#pls-bundle-id').val('');
        $('#pls-bundle-form')[0].reset();
      }
      $('#pls-bundle-modal').addClass('is-active');
    }

    function closeBundleModal() {
      $('#pls-bundle-modal').removeClass('is-active');
      $('#pls-bundle-form')[0].reset();
      $('#pls-bundle-errors').hide();
    }

    $('#pls-create-bundle, #pls-create-bundle-empty').on('click', function(e) {
      e.preventDefault();
      openBundleModal(null);
    });

    $(document).on('click', '.pls-edit-bundle', function(e) {
      e.preventDefault();
      var bundleId = $(this).data('bundle-id');
      if (!bundleId) return;
      
      $.post(ajaxurl, {
        action: 'pls_get_bundle',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        bundle_id: bundleId
      }, function(resp) {
        if (resp && resp.success && resp.data && resp.data.bundle) {
          openBundleModal(resp.data.bundle);
        } else {
          alert('Could not load bundle data.');
        }
      }).fail(function() {
        alert('Could not load bundle data.');
      });
    });

    $('#pls-bundle-modal .pls-modal__close, #pls-bundle-modal .pls-modal-cancel').on('click', function(e) {
      e.preventDefault();
      closeBundleModal();
    });

    $('#pls-bundle-form').on('submit', function(e) {
      e.preventDefault();
      var form = $(this);
      var submitBtn = form.find('button[type=submit]');
      var originalText = submitBtn.text();
      
      submitBtn.prop('disabled', true).text('Saving...');
      $('#pls-bundle-errors').hide();
      
      var formData = form.serializeArray();
      formData.push({ name: 'action', value: 'pls_save_bundle' });
      formData.push({ name: 'nonce', value: (window.PLS_Admin ? PLS_Admin.nonce : '') });
      
      $.post(ajaxurl, formData, function(resp) {
        if (resp && resp.success) {
          closeBundleModal();
          window.location.reload();
        } else {
          var errorMsg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Could not save bundle.';
          alert(errorMsg);
          submitBtn.prop('disabled', false).text(originalText);
        }
      }).fail(function() {
        alert('Could not save bundle.');
        submitBtn.prop('disabled', false).text(originalText);
      });
    });

    $(document).on('click', '.pls-delete-bundle', function(e) {
      e.preventDefault();
      var bundleId = $(this).data('bundle-id');
      if (!bundleId) return;
      
      if (!confirm('Delete this bundle and trash its WooCommerce product?')) {
        return;
      }
      
      var button = $(this);
      button.prop('disabled', true);
      
      $.post(ajaxurl, {
        action: 'pls_delete_bundle',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        bundle_id: bundleId
      }, function(resp) {
        if (resp && resp.success) {
          $('.pls-card[data-bundle-id="' + bundleId + '"]').fadeOut(300, function() {
            $(this).remove();
            if ($('.pls-card-grid .pls-card').length === 0) {
              window.location.reload();
            }
          });
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not delete bundle.');
          button.prop('disabled', false);
        }
      }).fail(function() {
        alert('Could not delete bundle.');
        button.prop('disabled', false);
      });
    });

    $(document).on('click', '.pls-sync-bundle', function(e) {
      e.preventDefault();
      var bundleId = $(this).data('bundle-id');
      if (!bundleId) return;
      
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Syncing...');
      
      $.post(ajaxurl, {
        action: 'pls_sync_bundle',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        bundle_id: bundleId
      }, function(resp) {
        if (resp && resp.success) {
          alert((resp.data && resp.data.message) ? resp.data.message : 'Bundle synced successfully.');
          window.location.reload();
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not sync bundle.');
          button.prop('disabled', false).text(originalText);
        }
      }).fail(function() {
        alert('Could not sync bundle.');
        button.prop('disabled', false).text(originalText);
      });
    });

    // Auto-update SKU count and units based on bundle type
    $('#bundle_type').on('change', function() {
      var bundleType = $(this).val();
      var skuCount = 0;
      var unitsPerSku = 0;
      
      switch(bundleType) {
        case 'mini_line':
          skuCount = 2;
          unitsPerSku = 250;
          break;
        case 'starter_line':
          skuCount = 3;
          unitsPerSku = 300;
          break;
        case 'growth_line':
          skuCount = 4;
          unitsPerSku = 400;
          break;
        case 'premium_line':
          skuCount = 6;
          unitsPerSku = 500;
          break;
      }
      
      if (skuCount > 0) {
        $('#sku_count').val(skuCount);
      }
      if (unitsPerSku > 0) {
        $('#units_per_sku').val(unitsPerSku);
      }
    });
  });
})(jQuery);
