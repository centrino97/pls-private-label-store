<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap pls-wrap">
  <h1>PLS – Bundles & Deals</h1>
  <p><em>Phase 0 scaffold:</em> implement bundle composer + offer triggers + “Sync bundle product(s)”.</p>

  <div class="notice notice-info"><p>Phase 1 recommended: bundle is a simple product that, on add-to-cart, injects child line items (pack variations) and groups them.</p></div>

  <form method="post">
    <?php wp_nonce_field('pls_bundles_sync'); ?>
    <input type="hidden" name="pls_bundles_sync" value="1" />
    <button class="button button-primary">Run Bundle Sync Stub</button>
  </form>

  <?php
  if ( isset($_POST['pls_bundles_sync']) && check_admin_referer('pls_bundles_sync') ) {
      $result = PLS_WC_Sync::sync_bundles_stub();
      echo '<div class="notice notice-success"><p>' . esc_html( $result ) . '</p></div>';
  }
  ?>
</div>
