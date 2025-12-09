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
      if (window.PLS_ProductAdmin && Array.isArray(PLS_ProductAdmin.ingredients)) {
        PLS_ProductAdmin.ingredients.forEach(function(term){
          var key = term.term_id || term.id;
          ingredientMap[key] = term;
        });
      }
      var defaultIngredientIcon = (window.PLS_ProductAdmin && PLS_ProductAdmin.defaultIngredientIcon) || ($('#pls-ingredient-chips').data('default-icon') || '');

      function renderIngredientLabel(term, inputName, isChecked){
        var label = $('<label class="pls-chip-select pls-chip-select--rich"></label>');
        var input = $('<input type="checkbox" />').attr('name', inputName || '').val(term.id);
        if (isChecked) { input.prop('checked', true); }
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
        label.append(input).append(media).append(copy);
        return label;
      }

      function renderIngredientList(list, selectedIds, keySelected){
        var wrap = $('#pls-ingredient-chips');
        if (!wrap.length) { return; }
        var preserved = Array.isArray(selectedIds) ? selectedIds.map(function(id){ return parseInt(id,10); }) : wrap.find('input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        var preservedKey = Array.isArray(keySelected) ? keySelected.map(function(id){ return parseInt(id,10); }) : $('#pls-key-ingredients input:checked').map(function(){ return parseInt($(this).val(), 10); }).get();
        wrap.empty();
        list.forEach(function(term){
          var normId = parseInt(term.term_id || term.id, 10);
          if (!normId) { return; }
          term.id = normId;
          ingredientMap[normId] = term;
          var chip = renderIngredientLabel(term, 'ingredient_ids[]', preserved.indexOf(normId) !== -1);
          wrap.append(chip);
        });
        updateKeyIngredients(preservedKey);
      }

      function parseIngredientInput(raw){
        var items = [];
        if (!raw) { return items; }
        raw.split(/\n|,/).forEach(function(line){
          var piece = line.trim();
          if (!piece) { return; }
          var parts = piece.split('|');
          var name = (parts.shift() || '').trim();
          var short = parts.join('|').trim();
          if (name) {
            items.push({ name: name, short_description: short });
          }
        });
        return items;
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

      var keyHintDefault = $('#pls-key-ingredients-hint').text();
      var keyHintReady = $('#pls-key-ingredients-hint').data('readyText') || 'Choose which ingredients to spotlight with icons.';

      if (Object.keys(ingredientMap).length){
        renderIngredientList(Object.values(ingredientMap));
      }

      function resetModal(){
        var form = $('#pls-product-form');
        if (form.length) {
          form[0].reset();
        }
        $('#pls-product-id, #pls-gallery-ids, #pls-featured-id').val('');
        $('#pls-featured-preview, #pls-gallery-preview').empty();
        $('#pls-ingredient-chips input[type=checkbox], #pls-category-pills input[type=checkbox], .pls-chip-group input[type=checkbox]').prop('checked', false);
        $('#pls-benefits, #pls-long-description, #pls-short-description, #pls-directions, #pls-ingredients-input, #pls-new-ingredients').val('');
        $('#pls-ingredient-chips .pls-chip-row').remove();
        $('#pls-attribute-rows').empty();
        $('#pls-label-guide').val(defaultLabelGuide);
        $('#pls-label-price').val('');
        $('#pls-label-file').prop('checked', false);
        setLabelState(true);
        $('#pls-product-errors').hide().find('ul').empty();
        goToStep('general');
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

      function populateModal(data){
        resetModal();
        if (!data) { return; }
        $('#pls-modal-title').text('Edit product');
        $('#pls-product-id').val(data.id);
        $('#pls-name').val(data.name || '');
        $('#pls-short-description').val(data.short_description || '');
        $('#pls-long-description').val(data.long_description || '');
        $('#pls-directions').val(data.directions_text || '');

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
        updateKeyIngredients(keySelections);

        if (data.featured_image_id){
          $('#pls-featured-id').val(data.featured_image_id);
          var img = $('<div class="pls-thumb"></div>').text('#'+data.featured_image_id);
          $('#pls-featured-preview').html(img);
        }

        if (Array.isArray(data.gallery_ids)){
          $('#pls-gallery-ids').val(data.gallery_ids.join(','));
          var wrap = $('<div class="pls-thumb-row"></div>');
          data.gallery_ids.forEach(function(id){ wrap.append($('<span class="pls-thumb"></span>').text('#'+id)); });
          $('#pls-gallery-preview').html(wrap);
        }

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
        updateKeyIngredients();
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
        if (!$('#pls-short-description').val().trim()){
          errors.push('Short description is required.');
        }
        if (!$('#pls-long-description').val().trim()){
          errors.push('Long description is required.');
        }
        if (!$('#pls-ingredient-chips input:checked').length){
          errors.push('Select at least one ingredient.');
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
        $('#pls-featured-id').val(files[0].id);
        $('#pls-featured-preview').html('<div class="pls-thumb">#'+files[0].id+'</div>');
      }, false);
    });

    $('#pls-pick-gallery').on('click', function(e){
      e.preventDefault();
      pickImage(function(files){
        var ids = files.map(function(file){ return file.id; });
        $('#pls-gallery-ids').val(ids.join(','));
        var wrap = $('<div class="pls-thumb-row"></div>');
        ids.forEach(function(id){ wrap.append($('<span class="pls-thumb"></span>').text('#'+id)); });
        $('#pls-gallery-preview').html(wrap);
      }, true);
    });

    $('#pls-push-new-ingredients').on('click', function(e){
      e.preventDefault();
      var raw = $('#pls-ingredients-input').val();
      var parsed = parseIngredientInput(raw);
      var button = $(this);
      if (!parsed.length){ return; }
      button.prop('disabled', true).text(button.data('busy-label') || 'Creating...');
      $.post(ajaxurl, { action: 'pls_create_ingredients', nonce: (window.PLS_Admin ? PLS_Admin.nonce : ''), ingredients: parsed }, function(resp){
        button.prop('disabled', false).text(button.data('label') || 'Create missing in Ingredients base');
        if (!resp || !resp.success || !resp.data || !Array.isArray(resp.data.ingredients)){
          alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Could not create ingredients.');
          return;
        }
        if (resp.data.default_icon){
          defaultIngredientIcon = resp.data.default_icon;
        }
        renderIngredientList(resp.data.ingredients);
        $('#pls-ingredients-input').val('');
      }).fail(function(){
        button.prop('disabled', false).text(button.data('label') || 'Create missing in Ingredients base');
        alert('Could not create ingredients.');
      });
    });

    $('#pls-product-form').on('submit', function(e){
        $('#pls-new-ingredients').val('');
        var errors = validateProductForm();
        if (errors.length){
          e.preventDefault();
          renderErrors(errors);
        } else {
          renderErrors([]);
        }
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
