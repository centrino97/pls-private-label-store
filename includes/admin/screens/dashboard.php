<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap pls-wrap">
  <h1>Private Label (PLS)</h1>
  <p>This plugin is designed to sit on top of WooCommerce + Elementor Pro (Hello Elementor theme). It stores your internal model in custom tables and syncs into WooCommerce products/variations/attributes so Elementor templates can render them natively.</p>

  <div class="pls-cards">
    <div class="pls-card">
      <h2>1) Products & Packs</h2>
      <p>Create base products and pack tiers (Trial/Starter/Brand Entry/Growth/Wholesale), then sync to Woo variable products + variations.</p>
      <a class="button button-primary" href="<?php echo esc_url( admin_url('admin.php?page=pls-products') ); ?>">Open Products & Packs</a>
    </div>

    <div class="pls-card">
      <h2>2) Attributes & Swatches</h2>
      <p>Manage a central attribute/value library with swatch styling and SEO fields. Sync to Woo global attributes + terms.</p>
      <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=pls-attributes') ); ?>">Open Attributes & Swatches</a>
    </div>

    <div class="pls-card">
      <h2>3) Bundles & Deals</h2>
      <p>Define Business Line bundles and deal surfaces (PDP / Cart upgrades). Bundles can add multiple pack items to cart.</p>
      <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=pls-bundles') ); ?>">Open Bundles & Deals</a>
    </div>
  </div>

  <hr />
  <h2>Elementor Integration</h2>
  <p>In Elementor Theme Builder, use the provided widgets:</p>
  <ul>
    <li><strong>PLS Configurator</strong> – pack tiers and swatches on Single Product templates</li>
    <li><strong>PLS Bundle Offer</strong> – upgrade/offer card on PDP or Cart templates</li>
  </ul>

  <p>Docs are included in <code>/docs</code> inside this plugin folder.</p>
</div>
