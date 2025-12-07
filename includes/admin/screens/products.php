<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action = isset($_GET['pls_action']) ? sanitize_text_field( wp_unslash($_GET['pls_action']) ) : '';
?>
<div class="wrap pls-wrap">
  <h1>PLS – Products & Packs</h1>
  <p><em>Phase 0 scaffold:</em> this screen is wired for your team to implement CRUD + bulk “Sync to WooCommerce”. Repositories + WC sync class are included.</p>

  <div class="notice notice-info"><p>Next dev step: implement list + editor UI (base products), pack tier repeater, validations, and a "Dry-run Sync" preview.</p></div>

  <h2>Quick Sync (dev utility)</h2>
  <p>This button calls a stub that your devs will replace with real sync logic.</p>
  <form method="post">
    <?php wp_nonce_field('pls_products_sync'); ?>
    <input type="hidden" name="pls_products_sync" value="1" />
    <button class="button button-primary">Run Sync Stub</button>
  </form>

  <?php
  if ( isset($_POST['pls_products_sync']) && check_admin_referer('pls_products_sync') ) {
      $result = PLS_WC_Sync::sync_all_stub();
      echo '<div class="notice notice-success"><p>' . esc_html( $result ) . '</p></div>';
  }
  ?>
</div>
