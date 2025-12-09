(function($){
  function switchTab(tab){
    $('.pls-tabs-nav .nav-tab').removeClass('nav-tab-active');
    $('.pls-tab-panel').removeClass('is-active');
    $('.pls-tabs-nav .nav-tab[data-pls-tab="'+tab+'"]').addClass('nav-tab-active');
    $('.pls-tab-panel[data-pls-tab="'+tab+'"]').addClass('is-active');
  }

  $(function(){
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

    function slugify(text){
      return text.toString().toLowerCase().trim()
        .replace(/[^a-z0-9-\s]/g,'')
        .replace(/\s+/g,'-')
        .replace(/-+/g,'-');
    }

    function resetModal(){
      var form = $('#pls-product-form');
      if (form.length) {
        form[0].reset();
      }
      $('#pls-product-id').val('');
      $('#pls-gallery-ids').val('');
      $('#pls-featured-preview').empty();
      $('#pls-gallery-preview').empty();
      $('#pls-ingredient-chips input[type=checkbox], #pls-key-ingredients input[type=checkbox], #pls-categories option, .pls-chip-group input[type=checkbox]').prop('checked', false);
      $('#pls-categories').val([]);
      $('#pls-benefits').val('');
      $('#pls-long-description').val('');
      $('#pls-short-description').val('');
      $('#pls-directions').val('');
      $('#pls-ingredients-input').val('');
      $('#pls-new-ingredients').val('');
      $('#pls-key-ingredients input[type=checkbox]').prop('checked', false);
      $('#pls-ingredient-chips .pls-chip-row').remove();
      $('#pls-status').val('draft');
      // reset pack grid
      $('#pls-pack-grid .pls-pack-row').each(function(idx){
        var defaults = (window.PLS_ProductAdmin && PLS_ProductAdmin.packDefaults) ? PLS_ProductAdmin.packDefaults : [];
        var unitVal = defaults[idx] || '';
        $(this).find('input[name*="[units]"]').val(unitVal);
        $(this).find('input[name*="[price]"]').val('');
        $(this).find('input[name*="[enabled]"]').prop('checked', true);
      });
    }

    function populateModal(data){
      resetModal();
      if (!data) { return; }
      $('#pls-modal-title').text('Edit product');
      $('#pls-product-id').val(data.id);
      $('#pls-name').val(data.name);
      $('#pls-slug').val(data.slug);
      $('#pls-status').val(data.status);
      $('#pls-short-description').val(data.short_description || '');
      $('#pls-long-description').val(data.long_description || '');
      $('#pls-directions').val(data.directions_text || '');
      $('#pls-categories').val(data.categories || []);

      if (Array.isArray(data.pack_tiers)){
        $('#pls-pack-grid .pls-pack-row').each(function(idx){
          var row = data.pack_tiers[idx];
          if (!row) { return; }
          $(this).find('input[name*="[units]"]').val(row.units || '');
          $(this).find('input[name*="[price]"]').val(row.price || '');
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
      if (Array.isArray(data.key_ingredients)){
        data.key_ingredients.forEach(function(item){
          var candidate = item.term_id || item.id;
          if (candidate) {
            $('#pls-key-ingredients input[value="'+candidate+'"]').prop('checked', true);
          }
        });
      }

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

      $('#pls-label-enabled').prop('checked', !!parseInt(data.label_enabled));
      $('#pls-label-price').val(data.label_price_per_unit || '');
      $('#pls-label-file').prop('checked', !!parseInt(data.label_requires_file));
      $('#pls-label-helper').val(data.label_helper_text || '');
      $('#pls-label-guide').val(data.label_guide_url || '');
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

    $('#pls-name').on('input', function(){
      var currentSlug = $('#pls-slug').val();
      if (!currentSlug){
        $('#pls-slug').val(slugify($(this).val()));
      }
    });

    $('.pls-modal__close, #pls-modal-cancel').on('click', function(){
      closeModal();
    });

    function pickImage(callback, multiple){
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
      $('#pls-new-ingredients').val(raw);
      if (raw){
        var pills = raw.split(',').map(function(i){ return i.trim(); }).filter(Boolean);
        if (pills.length){
          var chipRow = $('<div class="pls-chip-row"></div>');
          pills.forEach(function(p){ chipRow.append('<span class="pls-chip">'+p+'</span>'); });
          $('#pls-ingredient-chips').append(chipRow);
        }
      }
    });

    $('#pls-product-form').on('submit', function(){
      $('#pls-new-ingredients').val($('#pls-ingredients-input').val());
    });
  });
})(jQuery);
