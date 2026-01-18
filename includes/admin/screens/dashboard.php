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
  
  <h2><?php esc_html_e( 'Label Application Pricing', 'pls-private-label-store' ); ?></h2>
  <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) : ?>
    <div class="notice notice-success is-dismissible" style="margin: 20px 0;">
      <p><?php esc_html_e( 'Label pricing settings saved successfully.', 'pls-private-label-store' ); ?></p>
    </div>
  <?php endif; ?>
  <div class="pls-settings-section" style="background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 20px; margin: 20px 0; max-width: 600px;">
    <h3 style="margin-top: 0;"><?php esc_html_e( 'Global Label Pricing Rules', 'pls-private-label-store' ); ?></h3>
    <p class="description"><?php esc_html_e( 'Set automatic pricing for label application based on tier. Tier 3-5 are automatically FREE.', 'pls-private-label-store' ); ?></p>
    
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <?php wp_nonce_field( 'pls_save_label_settings', 'pls_label_settings_nonce' ); ?>
      <input type="hidden" name="action" value="pls_save_label_settings" />
      
      <table class="pls-settings-table" style="width: 100%; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #e2e8f0;">
          <td style="padding: 12px 0; font-weight: 600;"><?php esc_html_e( 'Tier 1-2:', 'pls-private-label-store' ); ?></td>
          <td style="padding: 12px 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span>$</span>
              <input type="number" step="0.01" name="label_price_tier_1_2" 
                     value="<?php echo esc_attr( get_option( 'pls_label_price_tier_1_2', '0.50' ) ); ?>" 
                     style="width: 100px; padding: 6px;" min="0" />
              <span><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
            </div>
            <p class="description" style="margin: 4px 0 0; font-size: 12px; color: #646970;">
              <?php esc_html_e( 'This price will be multiplied by the number of units in the pack tier.', 'pls-private-label-store' ); ?>
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding: 12px 0; font-weight: 600;"><?php esc_html_e( 'Tier 3-5:', 'pls-private-label-store' ); ?></td>
          <td style="padding: 12px 0;">
            <strong style="color: #00a32a;"><?php esc_html_e( 'FREE', 'pls-private-label-store' ); ?></strong>
            <span style="margin-left: 8px; color: #646970; font-size: 13px;">
              <?php esc_html_e( '(automatically applied)', 'pls-private-label-store' ); ?>
            </span>
          </td>
        </tr>
      </table>
      
      <p style="margin-top: 16px;">
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'pls-private-label-store' ); ?></button>
      </p>
    </form>
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
