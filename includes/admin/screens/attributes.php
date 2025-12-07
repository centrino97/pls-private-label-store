<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap pls-wrap">
  <h1>PLS – Attributes & Swatches</h1>
  <p><em>Phase 0 scaffold:</em> implement CRUD for attributes/values + swatch settings, then “Sync to Woo”.</p>

  <div class="notice notice-info"><p>Recommended: store attribute/value/swatch data in PLS tables and sync into Woo global attributes + terms (term meta or PLS swatch table mapping).</p></div>

  <form method="post">
    <?php wp_nonce_field('pls_attrs_sync'); ?>
    <input type="hidden" name="pls_attrs_sync" value="1" />
    <button class="button button-primary">Run Attribute Sync Stub</button>
  </form>

  <?php
  if ( isset($_POST['pls_attrs_sync']) && check_admin_referer('pls_attrs_sync') ) {
      $result = PLS_WC_Sync::sync_attributes_stub();
      echo '<div class="notice notice-success"><p>' . esc_html( $result ) . '</p></div>';
  }
  ?>
</div>
