(function($){
  function switchTab(tab){
    $('.pls-tabs-nav .nav-tab').removeClass('nav-tab-active');
    $('.pls-tab-panel').removeClass('is-active');
    $('.pls-tabs-nav .nav-tab[data-pls-tab="'+tab+'"]').addClass('nav-tab-active');
    $('.pls-tab-panel[data-pls-tab="'+tab+'"]').addClass('is-active');
  }

  $(function(){
    var stepOrder = ['general','data','ingredients','attributes','packs','label'];
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

    var attrMap = {};
    if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.attributes)) {
      PLS_ProductAdmin.attributes.forEach(function(attr){
        attrMap[attr.id] = attr;
      });
    }

      goToStep('general');

      var ingredientMap = {};
      var ingredientFilter = '';
      var ingredientSearchTimer = null;
      var gallerySelection = [];
      if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.ingredients)) {
        PLS_ProductAdmin.ingredients.forEach(function(term){
          var key = term.term_id || term.id;
          term.id = parseInt(key, 10);
          ingredientMap[key] = term;
        });
      }
      var defaultIngredientIcon = (window.PLS_ProductAdmin && PLS_ProductAdmin.defaultIngredientIcon) || ($('#pls-ingredient-chips').data('default-icon') || '');

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

      function renderSpotlight(selectedIds){
        var wrap = $('#pls-ingredient-spotlight');
        if (!wrap.length){ return; }
        wrap.empty();
        selectedIds.forEach(function(id){
          var term = ingredientMap[id] || { id: id, name: '#'+id, icon: defaultIngredientIcon };
          wrap.append(renderIngredientLabel(term, '', false));
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

        Object.values(ingredientMap).forEach(function(term){
          var normId = parseInt(term.term_id || term.id, 10);
          if (!normId || preserved.indexOf(normId) !== -1){ return; }
          var haystack = (term.name || term.label || '').toLowerCase();
          if (normalizedFilter && haystack.indexOf(normalizedFilter) === -1){ return; }
          term.id = normId;
          ordered.push(term);
        });

        ordered.forEach(function(term){
          wrap.append(renderIngredientOption(term, preserved.indexOf(term.id) !== -1));
        });

        renderSelectedIngredients(preserved);
        renderSpotlight(preserved);
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
        var thumb = $('<div class="pls-thumb"></div>');
        if (url){
          thumb.append($('<img/>').attr('src', url).attr('alt', ''));
        } else {
          thumb.text('#'+id);
        }
        var removeBtn = $('<button type="button" class="button-link-delete pls-remove-featured">Remove</button>');
        preview.append(thumb).append(removeBtn);
      }

      function syncGalleryField(){
        var ids = gallerySelection.map(function(item){ return item.id; });
        $('#pls-gallery-ids').val(ids.join(','));
      }

      function renderGalleryPreview(){
        var wrap = $('#pls-gallery-preview');
        wrap.empty();
        gallerySelection.forEach(function(item, index){
          var cell = $('<span class="pls-thumb"></span>');
          if (item.url){
            cell.append($('<img/>').attr('src', item.url).attr('alt', ''));
          } else {
            cell.text('#'+item.id);
          }
          var removeBtn = $('<button type="button" class="button-link-delete pls-remove-gallery" data-gallery-index="'+index+'">×</button>');
          var container = $('<span class="pls-thumb-wrap"></span>');
          container.append(cell).append(removeBtn);
          wrap.append(container);
        });
        syncGalleryField();
      }

      var keyHintDefault = $('#pls-key-ingredients-hint').text();
      var keyHintReady = $('#pls-key-ingredients-hint').data('readyText') || 'Choose which ingredients to spotlight with icons.';

      if (Object.keys(ingredientMap).length){
        renderIngredientList(ingredientFilter);
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
        renderSpotlight([]);
        updateKeyIngredients();
        $('#pls-pack-grid .pls-pack-row').each(function(idx){
          var defaults = (window.PLS_ProductAdmin && PLS_ProductAdmin.packDefaults) ? PLS_ProductAdmin.packDefaults : [];
          var unitVal = defaults[idx] || '';
          $(this).find('input[name*="[units]"]').val(unitVal);
          $(this).find('input[name*="[price]"]').val('');
          $(this).find('input[name*="[enabled]"]').prop('checked', true);
        });
      }

      function updateKeyIngredients(forceSelection){
        var wrap = $('#pls-key-ingredients');
        var hint = $('#pls-key-ingredients-hint');
        var selectedIngredients = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        var preserved = Array.isArray(forceSelection) ? forceSelection.map(function(id){ return parseInt(id, 10); }) : wrap.find('input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        wrap.empty();
        if (!selectedIngredients.length){
          hint.text(keyHintDefault);
          return;
        }
        hint.text(keyHintReady);
        selectedIngredients.forEach(function(id){
          var term = ingredientMap[id] || { id: id, name: '#'+id, icon: defaultIngredientIcon };
          wrap.append(renderIngredientLabel(term, 'key_ingredient_ids[]', preserved.indexOf(id) !== -1));
        });
      }

      function populateValueOptions(attrRow, selectEl){
        var attrId = parseInt(attrRow.find('.pls-attr-select').val(), 10);
        var placeholder = selectEl.data('placeholder') || 'Select value';
        selectEl.empty();
        selectEl.append('<option value="">'+placeholder+'</option>');
        if (attrMap[attrId] && Array.isArray(attrMap[attrId].values)){
          attrMap[attrId].values.forEach(function(val){
            selectEl.append('<option value="'+val.id+'">'+val.label+'</option>');
          });
        }
        selectEl.append('<option value="__new__">Create new value</option>');
      }

      function setPriceAvailability(valueRow){
        var newLabel = valueRow.find('.pls-attr-value-new').val() || '';
        var currentSelect = valueRow.find('.pls-attr-value').val();
        var hasValue = (!!currentSelect && currentSelect !== '__new__') || !!newLabel.trim();
        valueRow.find('.pls-attr-price').prop('disabled', !hasValue);
      }

      function syncValueRow(valueRow){
        var attrRow = valueRow.closest('.pls-attribute-row');
        var attrSelect = attrRow.find('.pls-attr-select');
        var isNewAttr = attrSelect.val() === '__new__';
        var select = valueRow.find('.pls-attr-value');
        if (!isNewAttr){
          populateValueOptions(attrRow, select);
        }
        select.prop('disabled', isNewAttr);
        valueRow.find('.pls-attr-value-new-wrap').toggle(isNewAttr || select.val() === '__new__');
        setPriceAvailability(valueRow);
      }

      function renumberAttributeRows(){
        $('#pls-attribute-rows .pls-attribute-row').each(function(idx){
          $(this).find('.pls-attr-select').attr('name', 'attr_options['+idx+'][attribute_id]');
          $(this).find('.pls-attr-new').attr('name', 'attr_options['+idx+'][attribute_label]');
          $(this).find('.pls-attribute-value-row').each(function(vIdx){
            $(this).find('.pls-attr-value').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][value_id]');
            $(this).find('.pls-attr-value-new').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][value_label]');
            $(this).find('.pls-attr-price').attr('name', 'attr_options['+idx+'][values]['+vIdx+'][price]');
          });
        });
      }

      function hydrateAttributeValues(attrRow){
        var attrId = parseInt(attrRow.find('.pls-attr-select').val(), 10);
        if (!attrId) { return; }
        var valuesWrap = attrRow.find('.pls-attribute-values');
        valuesWrap.empty();
        var presets = (attrMap[attrId] && Array.isArray(attrMap[attrId].values)) ? attrMap[attrId].values : [];
        if (!presets.length){
          addValueRow(attrRow, {});
          return;
        }
        presets.forEach(function(val){
          addValueRow(attrRow, { value_id: val.id, price: formatPrice(val.price || 0) });
        });
      }

      function addValueRow(attrRow, data){
        data = data || {};
        var template = attrRow.find('.pls-attribute-value-template .pls-attribute-value-row').first().clone();
        attrRow.find('.pls-attribute-values').append(template);
        renumberAttributeRows();
        populateValueOptions(attrRow, template.find('.pls-attr-value'));
        if (data.value_id){
          template.find('.pls-attr-value').val(data.value_id);
        } else if (data.value_label){
          template.find('.pls-attr-value').val('__new__');
          template.find('.pls-attr-value-new').val(data.value_label);
        }
        if (typeof data.price !== 'undefined'){ template.find('.pls-attr-price').val(formatPrice(data.price)); }
        syncValueRow(template);
      }

      function syncAttributeRow(attrRow, forceHydrate){
        var attrSelect = attrRow.find('.pls-attr-select');
        var isNewAttr = attrSelect.val() === '__new__';
        attrRow.toggleClass('pls-attribute-row--new', isNewAttr);
        attrRow.find('.pls-attr-new-wrap').toggle(isNewAttr);
        if (!isNewAttr && (forceHydrate || !attrRow.find('.pls-attribute-value-row').length)){
          hydrateAttributeValues(attrRow);
        }
        attrRow.find('.pls-attribute-value-row').each(function(){ syncValueRow($(this)); });
      }

      function buildAttributeRow(data){
        data = data || {};
        var template = $('#pls-attribute-template .pls-attribute-row').first().clone();
        $('#pls-attribute-rows').append(template);
        renumberAttributeRows();

        if (data.attribute_id){
          template.find('.pls-attr-select').val(data.attribute_id);
        } else if (data.attribute_label){
          template.find('.pls-attr-select').val('__new__');
          template.find('.pls-attr-new').val(data.attribute_label);
        }

        var values = Array.isArray(data.values) ? data.values : [];
        if (!values.length && (data.value_id || data.value_label)){
          values = [{ value_id: data.value_id, value_label: data.value_label, price: data.price }];
        }
        if (!values.length){ values.push({}); }

        values.forEach(function(row){ addValueRow(template, row); });

        syncAttributeRow(template, false);
      }

      function addAttributeToMap(attr){
        if (!attr || !attr.id){ return; }
        attrMap[attr.id] = { id: attr.id, label: attr.label, values: [] };
        $('.pls-attr-select').each(function(){
          if (!$(this).find('option[value="'+attr.id+'"]').length){
            $(this).append('<option value="'+attr.id+'">'+attr.label+'</option>');
          }
        });
        if (!$('#pls-value-attribute option[value="'+attr.id+'"]').length){
          $('#pls-value-attribute').append('<option value="'+attr.id+'">'+attr.label+'</option>');
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
        attrMap[attributeId].values.push({ id: value.id, label: value.label });
        $('.pls-attribute-row').each(function(){
          var attrId = parseInt($(this).find('.pls-attr-select').val(), 10);
          if (attrId === attributeId){
            var selectEl = $(this).find('.pls-attr-value');
            var currentVal = selectEl.val();
            populateValueOptions($(this), selectEl);
            if (currentVal){
              selectEl.val(currentVal);
            }
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
          });
        }

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
        renderSpotlight(selectedIngredients);
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

    function openModal(data){
      if (!data){ resetModal(); $('#pls-modal-title').text('Create product'); }
      $('.pls-modal').addClass('is-active');
      $('body').addClass('pls-modal-open');
      if (data){ populateModal(data); }
    }

    function closeModal(){
      $('.pls-modal').removeClass('is-active');
      $('body').removeClass('pls-modal-open');
    }

    $('#pls-open-product-modal').on('click', function(){
      openModal(null);
    });

    $('.pls-edit-product').on('click', function(e){
      e.preventDefault();
      var id = $(this).data('product-id');
      openModal(productMap[id]);
    });

      $('.pls-modal__close, #pls-modal-cancel').on('click', function(){
        closeModal();
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
      $('#pls-ingredient-create-modal').addClass('is-active');
      $('body').addClass('pls-modal-open');
    });

    $('#pls-cancel-ingredient-create, #pls-ingredient-create-modal .pls-modal__close').on('click', function(e){
      e.preventDefault();
      $('#pls-ingredient-create-modal').removeClass('is-active');
      if (!$('#pls-product-modal').hasClass('is-active')){
        $('body').removeClass('pls-modal-open');
      }
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
        $('#pls-ingredient-create-modal').removeClass('is-active');
        if (!$('#pls-product-modal').hasClass('is-active')){
          $('body').removeClass('pls-modal-open');
        }
        $('#pls-new-ingredient-name').val('');
        $('#pls-new-ingredient-short').val('');
        $('#pls-new-ingredient-icon').val('');
        $('#pls-new-ingredient-icon-preview').empty();
        var newId = resp.data.ingredients.length ? (resp.data.ingredients[0].id || resp.data.ingredients[0].term_id) : 0;
        if (newId){
          $('#pls-ingredient-chips input[value="'+newId+'"]').prop('checked', true);
          var selected = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
          renderSelectedIngredients(selected);
          renderSpotlight(selected);
          updateKeyIngredients();
        }
      }).fail(function(){
        alert('Could not create ingredient.');
      }).always(function(){
        submitBtn.prop('disabled', false).text(originalText);
      });
    });

    $('#pls-open-attribute-modal').on('click', function(e){
      e.preventDefault();
      $('#pls-attribute-create-modal').addClass('is-active');
      $('body').addClass('pls-modal-open');
    });

    $('#pls-cancel-attribute-create, #pls-attribute-create-modal .pls-modal__close').on('click', function(e){
      e.preventDefault();
      $('#pls-attribute-create-modal').removeClass('is-active');
      if (!$('#pls-product-modal').hasClass('is-active')){
        $('body').removeClass('pls-modal-open');
      }
    });

    $('#pls-create-attribute-form').on('submit', function(e){
      e.preventDefault();
      var label = ($('#pls-new-attribute-label').val() || '').trim();
      if (!label){
        alert('Attribute label is required.');
        return;
      }
      var payload = {
        action: 'pls_create_attribute',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        label: label,
        is_variation: $('#pls-new-attribute-variation').is(':checked') ? 1 : 0
      };
      var submitBtn = $('#pls-save-attribute-create');
      var originalText = submitBtn.text();
      submitBtn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, payload, function(resp){
        if (!resp || !resp.success || !resp.data || !resp.data.attribute){
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create attribute.');
          return;
        }
        addAttributeToMap(resp.data.attribute);
        $('#pls-attribute-create-modal').removeClass('is-active');
        if (!$('#pls-product-modal').hasClass('is-active')){
          $('body').removeClass('pls-modal-open');
        }
        $('#pls-new-attribute-label').val('');
        $('#pls-new-attribute-variation').prop('checked', true);
      }).fail(function(){
        alert('Could not create attribute.');
      }).always(function(){
        submitBtn.prop('disabled', false).text(originalText);
      });
    });

    $('#pls-open-value-modal').on('click', function(e){
      e.preventDefault();
      if (!$('#pls-value-attribute option').length){
        alert('Create an attribute first.');
        return;
      }
      var currentAttr = $('.pls-attribute-row').first().find('.pls-attr-select').val();
      if (currentAttr){
        $('#pls-value-attribute').val(currentAttr);
      }
      $('#pls-value-create-modal').addClass('is-active');
      $('body').addClass('pls-modal-open');
    });

    $('#pls-cancel-value-create, #pls-value-create-modal .pls-modal__close').on('click', function(e){
      e.preventDefault();
      $('#pls-value-create-modal').removeClass('is-active');
      if (!$('#pls-product-modal').hasClass('is-active')){
        $('body').removeClass('pls-modal-open');
      }
    });

    $('#pls-create-value-form').on('submit', function(e){
      e.preventDefault();
      var attrId = parseInt($('#pls-value-attribute').val(), 10);
      var label = ($('#pls-new-value-label').val() || '').trim();
      if (!attrId || !label){
        alert('Attribute and value label are required.');
        return;
      }
      var payload = {
        action: 'pls_create_attribute_value',
        nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''),
        attribute_id: attrId,
        label: label
      };
      var submitBtn = $('#pls-save-value-create');
      var originalText = submitBtn.text();
      submitBtn.prop('disabled', true).text('Saving...');
      $.post(ajaxurl, payload, function(resp){
        if (!resp || !resp.success || !resp.data || !resp.data.value){
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create value.');
          return;
        }
        addValueToMap(attrId, resp.data.value);
        $('#pls-value-create-modal').removeClass('is-active');
        if (!$('#pls-product-modal').hasClass('is-active')){
          $('body').removeClass('pls-modal-open');
        }
        $('#pls-new-value-label').val('');
      }).fail(function(){
        alert('Could not create value.');
      }).always(function(){
        submitBtn.prop('disabled', false).text(originalText);
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

      $(document).on('input', '.pls-attr-search', function(){
        var query = ($(this).val() || '').toLowerCase();
        var row = $(this).closest('.pls-attribute-row');
        var select = row.find('.pls-attr-select');
        if (!select.length || !query){ return; }
        var match = select.find('option').filter(function(){
          var val = $(this).val();
          return val && val !== '__new__' && $(this).text().toLowerCase().indexOf(query) !== -1;
        }).first();
        if (match.length){
          select.val(match.val()).trigger('change');
        }
      });

      $(document).on('change', '.pls-attr-value', function(){
        var row = $(this).closest('.pls-attribute-value-row');
        syncValueRow(row);
      });

      $(document).on('input', '.pls-attr-value-new', function(){
        setPriceAvailability($(this).closest('.pls-attribute-value-row'));
      });

      $(document).on('click', '.pls-attribute-remove', function(e){
        e.preventDefault();
        $(this).closest('.pls-attribute-row').remove();
        renumberAttributeRows();
      });

      $(document).on('click', '.pls-attribute-value-add', function(e){
        e.preventDefault();
        var attrRow = $(this).closest('.pls-attribute-row');
        addValueRow(attrRow, {});
      });

      $(document).on('click', '.pls-attribute-value-remove', function(e){
        e.preventDefault();
        var attrRow = $(this).closest('.pls-attribute-row');
        $(this).closest('.pls-attribute-value-row').remove();
        if (!attrRow.find('.pls-attribute-value-row').length){
          addValueRow(attrRow, {});
        }
        renumberAttributeRows();
      });

      $('#pls-ingredient-chips').on('change', 'input[type=checkbox]', function(){
        var selected = $('#pls-ingredient-chips input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        renderSelectedIngredients(selected);
        renderSpotlight(selected);
        updateKeyIngredients();
      });

      $('#pls-ingredient-search').on('input', function(){
        var value = ($(this).val() || '').toLowerCase();
        clearTimeout(ingredientSearchTimer);
        ingredientSearchTimer = setTimeout(function(){
          ingredientFilter = value;
          renderIngredientList(ingredientFilter);
        }, 200);
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
          var attrLabel = ($(this).find('.pls-attr-new').val() || '').trim();
          var hasAttribute = !!attrSelectVal || !!attrLabel;
          if (!hasAttribute){
            return;
          }
          if (attrSelectVal === '__new__' && !attrLabel){
            errors.push('Provide a label for each new attribute.');
          }
          var valueRows = $(this).find('.pls-attribute-value-row');
          if (!valueRows.length){
            errors.push('Add at least one value for each attribute.');
            return;
          }
          valueRows.each(function(){
            var selectVal = $(this).find('.pls-attr-value').val();
            var newLabel = ($(this).find('.pls-attr-value-new').val() || '').trim();
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
          closeModal();
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
