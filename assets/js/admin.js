(function($){
  function switchTab(tab){
    $('.pls-tabs-nav .nav-tab').removeClass('nav-tab-active');
    $('.pls-tab-panel').removeClass('is-active');
    $('.pls-tabs-nav .nav-tab[data-pls-tab="'+tab+'"]').addClass('nav-tab-active');
    $('.pls-tab-panel[data-pls-tab="'+tab+'"]').addClass('is-active');
  }

  $(function(){
    var stepOrder = ['general','data','ingredients','packs','attributes','label'];
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

        if (Array.isArray(data.attributes)){
          data.attributes.forEach(function(row){ buildAttributeRow(row); });
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

    $(document).on('click', '.pls-sync-product', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      if (!id){ return; }
      var button = $(this);
      var originalText = button.text();
      button.prop('disabled', true).text('Syncing...');
      $.post(ajaxurl, { action: 'pls_sync_product', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), id: id }, function(resp){
        if (resp && resp.success && resp.data && resp.data.product){
          productMap[id] = resp.data.product;
          updateCardSync(resp.data.product);
        } else {
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not sync product.');
        }
      }).fail(function(){
        alert('Could not sync product.');
      }).always(function(){
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
  });
})(jQuery);
