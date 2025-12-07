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

  $(function(){
    // Auto-load offers for any offer widget present.
    $('.pls-offer').each(function(){ loadOffers($(this)); });
  });
})(jQuery);
