<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$notice      = '';
$error       = '';
$created_any = false;

// Handle Delete
if ( isset( $_POST['pls_ingredient_delete'] ) && check_admin_referer( 'pls_ingredient_delete' ) ) {
    $term_id = isset( $_POST['ingredient_id'] ) ? absint( $_POST['ingredient_id'] ) : 0;
    if ( $term_id ) {
        $result = wp_delete_term( $term_id, 'pls_ingredient' );
        if ( ! is_wp_error( $result ) && $result !== false ) {
            $notice = __( 'Ingredient deleted.', 'pls-private-label-store' );
        } else {
            $error = __( 'Failed to delete ingredient.', 'pls-private-label-store' );
        }
    }
}

if ( isset( $_POST['pls_ingredient_edit'] ) && check_admin_referer( 'pls_ingredient_edit' ) ) {
    $edits = isset( $_POST['ingredient_edit'] ) && is_array( $_POST['ingredient_edit'] ) ? $_POST['ingredient_edit'] : array();

    foreach ( $edits as $term_id => $row ) {
        $term_id = absint( $term_id );
        if ( ! $term_id ) {
            continue;
        }

        $new_name    = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
        $icon_id     = isset( $row['icon_id'] ) ? absint( $row['icon_id'] ) : 0;
        $icon_url    = $icon_id ? wp_get_attachment_url( $icon_id ) : '';
        $short_descr = isset( $row['short_desc'] ) ? sanitize_text_field( wp_unslash( $row['short_desc'] ) ) : '';
        $is_active   = isset( $row['is_active'] ) ? 1 : 0;

        if ( $new_name ) {
            wp_update_term( $term_id, 'pls_ingredient', array( 'name' => $new_name ) );
        }

        update_term_meta( $term_id, 'pls_ingredient_icon_id', $icon_id );
        update_term_meta( $term_id, 'pls_ingredient_icon', $icon_url );
        update_term_meta( $term_id, 'pls_ingredient_short_desc', $short_descr );
        update_term_meta( $term_id, 'pls_ingredient_is_active', $is_active );
    }

    $notice = __( 'Ingredients updated.', 'pls-private-label-store' );
}

if ( isset( $_POST['pls_ingredient_add'] ) && check_admin_referer( 'pls_ingredient_add' ) ) {
    $name        = isset( $_POST['ingredient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ingredient_name'] ) ) : '';
    $icon_id     = isset( $_POST['ingredient_icon_id'] ) ? absint( $_POST['ingredient_icon_id'] ) : 0;
    $icon        = $icon_id ? wp_get_attachment_url( $icon_id ) : '';
    $short_descr = isset( $_POST['ingredient_short_desc'] ) ? sanitize_text_field( wp_unslash( $_POST['ingredient_short_desc'] ) ) : '';
    $is_active   = isset( $_POST['ingredient_is_active'] ) ? 1 : 0;

    if ( $name ) {
        $slug  = sanitize_title( $name );
        $maybe = term_exists( $slug, 'pls_ingredient' );
        if ( ! $maybe ) {
            $result = wp_insert_term( $name, 'pls_ingredient', array( 'slug' => $slug ) );
            if ( ! is_wp_error( $result ) ) {
                update_term_meta( $result['term_id'], 'pls_ingredient_icon_id', $icon_id );
                update_term_meta( $result['term_id'], 'pls_ingredient_icon', $icon );
                update_term_meta( $result['term_id'], 'pls_ingredient_short_desc', $short_descr );
                update_term_meta( $result['term_id'], 'pls_ingredient_is_active', $is_active );
                $notice = __( 'Ingredient saved.', 'pls-private-label-store' );
                $created_any = true;
            } else {
                $error = $result->get_error_message();
            }
        } else {
            $error = __( 'Ingredient already exists.', 'pls-private-label-store' );
        }
    }
}

if ( isset( $_POST['pls_ingredient_bulk'] ) && check_admin_referer( 'pls_ingredient_bulk' ) ) {
    $bulk_raw = isset( $_POST['bulk_ingredients'] ) ? wp_unslash( $_POST['bulk_ingredients'] ) : '';
    $parts    = array_filter( array_map( 'trim', explode( ',', $bulk_raw ) ) );

    foreach ( $parts as $part ) {
        $slug  = sanitize_title( $part );
        $maybe = term_exists( $slug, 'pls_ingredient' );
        if ( ! $maybe ) {
            $result = wp_insert_term( $part, 'pls_ingredient', array( 'slug' => $slug ) );
            if ( ! is_wp_error( $result ) ) {
                // Default to base ingredient (not active)
                update_term_meta( $result['term_id'], 'pls_ingredient_is_active', 0 );
                $created_any = true;
            }
        }
    }

    if ( $created_any ) {
        $notice = __( 'Missing ingredients created.', 'pls-private-label-store' );
    }
}

$ingredients = get_terms(
    array(
        'taxonomy'   => 'pls_ingredient',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $ingredients ) ) {
    $ingredients = array();
}

// Separate base and active ingredients
$base_ingredients = array();
$active_ingredients = array();
foreach ( $ingredients as $ing ) {
    $is_active = (int) get_term_meta( $ing->term_id, 'pls_ingredient_is_active', true );
    if ( $is_active ) {
        $active_ingredients[] = $ing;
    } else {
        $base_ingredients[] = $ing;
    }
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'all';
?>
<div class="wrap pls-wrap">
  <div class="pls-page-head">
      <div>
          <p class="pls-label"><?php esc_html_e( 'Ingredient Management', 'pls-private-label-store' ); ?></p>
          <h1 style="margin: 4px 0;"><?php esc_html_e( 'Ingredients Base', 'pls-private-label-store' ); ?></h1>
          <p class="description">
              <?php esc_html_e( 'Maintain your ingredient library. Base ingredients are always included in products. Active ingredients are selectable by customers at Tier 3+.', 'pls-private-label-store' ); ?>
              <span class="pls-help-icon" title="<?php esc_attr_e( 'Base/INCI ingredients form the foundation of every product. Active/Key ingredients (Tier 3+) become optional add-ons that customers can select during configuration.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
          </p>
      </div>
      <div style="display: flex; gap: 12px; align-items: center;">
          <span class="pls-badge"><?php echo count( $base_ingredients ); ?> <?php esc_html_e( 'Base', 'pls-private-label-store' ); ?></span>
          <span class="pls-badge pls-badge--success"><?php echo count( $active_ingredients ); ?> <?php esc_html_e( 'Active', 'pls-private-label-store' ); ?></span>
      </div>
  </div>

  <!-- Tabs Navigation -->
  <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'all', admin_url( 'admin.php?page=pls-ingredients' ) ) ); ?>" 
         class="nav-tab <?php echo $active_tab === 'all' ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e( 'All Ingredients', 'pls-private-label-store' ); ?>
          <span class="pls-tab-count"><?php echo count( $ingredients ); ?></span>
      </a>
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'base', admin_url( 'admin.php?page=pls-ingredients' ) ) ); ?>" 
         class="nav-tab <?php echo $active_tab === 'base' ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e( 'Base / INCI', 'pls-private-label-store' ); ?>
          <span class="pls-tab-count"><?php echo count( $base_ingredients ); ?></span>
      </a>
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'active', admin_url( 'admin.php?page=pls-ingredients' ) ) ); ?>" 
         class="nav-tab <?php echo $active_tab === 'active' ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e( 'Active / Key (T3+)', 'pls-private-label-store' ); ?>
          <span class="pls-tab-count"><?php echo count( $active_ingredients ); ?></span>
      </a>
  </nav>

  <?php if ( $notice ) : ?>
      <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>
  <?php if ( $error ) : ?>
      <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
  <?php endif; ?>

  <div class="pls-card pls-card--panel">
    <h2><?php esc_html_e( 'Add Ingredient', 'pls-private-label-store' ); ?></h2>
    <form method="post" class="pls-form">
      <?php wp_nonce_field( 'pls_ingredient_add' ); ?>
      <input type="hidden" name="pls_ingredient_add" value="1" />
      <div class="pls-field-row">
        <label><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label>
        <input type="text" name="ingredient_name" class="regular-text" placeholder="Hyaluronic Acid" required />
      </div>
      <div class="pls-field-row">
        <label><?php esc_html_e( 'Short description (optional)', 'pls-private-label-store' ); ?></label>
        <input type="text" name="ingredient_short_desc" class="regular-text" placeholder="Instantly plumps skin with moisture" />
      </div>
      <div class="pls-field-row">
        <label>
          <input type="checkbox" name="ingredient_is_active" value="1" />
          <?php esc_html_e( 'Active Ingredient (customer selectable at Tier 3+)', 'pls-private-label-store' ); ?>
          <span class="pls-help-icon" title="<?php esc_attr_e( 'When checked, this ingredient becomes a selectable option for Tier 3+ customers in the product configurator. Base ingredients are always included.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px; font-size: 12px;">ⓘ</span>
        </label>
        <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Uncheck for base/INCI ingredients that are not customer-selectable.', 'pls-private-label-store' ); ?></p>
      </div>
      <div class="pls-field-row">
        <label><?php esc_html_e( 'Icon (optional)', 'pls-private-label-store' ); ?></label>
        <div class="pls-icon-picker" data-target="ingredient_icon_id">
          <div class="pls-icon-preview" id="ingredient_icon_preview"></div>
          <input type="hidden" name="ingredient_icon_id" id="ingredient_icon_id" />
          <button type="button" class="button pls-icon-pick"><?php esc_html_e( 'Upload/Select icon', 'pls-private-label-store' ); ?></button>
          <button type="button" class="button-link-delete pls-icon-clear"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
        </div>
      </div>
      <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Ingredient', 'pls-private-label-store' ); ?></button></p>
    </form>
  </div>

  <div class="pls-card pls-card--panel">
    <h2><?php esc_html_e( 'Bulk Operations', 'pls-private-label-store' ); ?></h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
      <div>
        <h3><?php esc_html_e( 'Bulk Create', 'pls-private-label-store' ); ?></h3>
        <form method="post">
          <?php wp_nonce_field( 'pls_ingredient_bulk' ); ?>
          <input type="hidden" name="pls_ingredient_bulk" value="1" />
          <div class="pls-field-row">
            <label><?php esc_html_e( 'Comma separated list', 'pls-private-label-store' ); ?></label>
            <textarea name="bulk_ingredients" class="large-text" rows="4" placeholder="Vitamin C, Niacinamide, Retinol, Peptides"></textarea>
            <p class="description"><?php esc_html_e( 'Creates as base ingredients (Tier 1). Edit them after to mark as active/key ingredients.', 'pls-private-label-store' ); ?></p>
          </div>
          <p class="submit"><button class="button button-primary"><?php esc_html_e( 'Create missing entries', 'pls-private-label-store' ); ?></button></p>
        </form>
      </div>
      
      <div>
        <h3><?php esc_html_e( 'Export', 'pls-private-label-store' ); ?></h3>
        <p class="description"><?php esc_html_e( 'Export all ingredients as CSV for backup or import to another site.', 'pls-private-label-store' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
          <?php wp_nonce_field( 'pls_ingredient_export' ); ?>
          <input type="hidden" name="action" value="pls_export_ingredients" />
          <p class="submit"><button type="submit" class="button"><?php esc_html_e( 'Export Ingredients', 'pls-private-label-store' ); ?></button></p>
        </form>
      </div>
    </div>
  </div>

  <!-- Tab Description -->
  <?php if ( $active_tab === 'base' ) : ?>
      <div class="pls-tab-description" style="margin-bottom: 20px; padding: 12px 16px; background: var(--pls-gray-50); border-left: 3px solid var(--pls-accent); border-radius: 4px;">
          <strong><?php esc_html_e( 'Base / INCI Ingredients', 'pls-private-label-store' ); ?></strong>
          <p style="margin: 4px 0 0; color: var(--pls-gray-600);"><?php esc_html_e( 'These ingredients are always included in products by default. They form the foundation of your formulations and are not selectable by customers.', 'pls-private-label-store' ); ?></p>
      </div>
  <?php elseif ( $active_tab === 'active' ) : ?>
      <div class="pls-tab-description" style="margin-bottom: 20px; padding: 12px 16px; background: var(--pls-success-light); border-left: 3px solid var(--pls-success); border-radius: 4px;">
          <strong><?php esc_html_e( 'Active / Key Ingredients (Tier 3+)', 'pls-private-label-store' ); ?></strong>
          <p style="margin: 4px 0 0; color: var(--pls-gray-600);"><?php esc_html_e( 'These premium ingredients are optional add-ons that customers can select during product configuration. Only available to Tier 3+ customers. You can set price impacts per product.', 'pls-private-label-store' ); ?></p>
      </div>
  <?php endif; ?>

  <h2><?php esc_html_e( 'Existing ingredients', 'pls-private-label-store' ); ?></h2>
  <div class="pls-card-grid">
    <?php 
    // Filter ingredients based on active tab
    $display_ingredients = $ingredients;
    if ( $active_tab === 'base' ) {
        $display_ingredients = $base_ingredients;
    } elseif ( $active_tab === 'active' ) {
        $display_ingredients = $active_ingredients;
    }
    ?>
    <?php if ( empty( $display_ingredients ) ) : ?>
        <div class="pls-card" style="text-align: center; padding: 48px 24px; grid-column: 1 / -1;">
            <div style="font-size: 48px; color: var(--pls-gray-300); margin-bottom: 16px;">🧪</div>
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: var(--pls-gray-900);">
                <?php 
                if ( $active_tab === 'base' ) {
                    esc_html_e( 'No base ingredients yet', 'pls-private-label-store' );
                } elseif ( $active_tab === 'active' ) {
                    esc_html_e( 'No active ingredients yet', 'pls-private-label-store' );
                } else {
                    esc_html_e( 'No ingredients yet', 'pls-private-label-store' );
                }
                ?>
            </h3>
            <p style="margin: 0; color: var(--pls-gray-500);"><?php esc_html_e( 'Start adding your ingredient library using the form above.', 'pls-private-label-store' ); ?></p>
        </div>
    <?php else : ?>
        <form method="post" class="pls-card pls-card--panel" style="grid-column:1/-1;">
          <?php wp_nonce_field( 'pls_ingredient_edit' ); ?>
          <input type="hidden" name="pls_ingredient_edit" value="1" />
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width: 20%;"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></th>
                <th style="width: 10%;"><?php esc_html_e( 'Slug', 'pls-private-label-store' ); ?></th>
                <th style="width: 15%;"><?php esc_html_e( 'Type', 'pls-private-label-store' ); ?></th>
                <th style="width: 20%;"><?php esc_html_e( 'Short description', 'pls-private-label-store' ); ?></th>
                <th style="width: 15%;"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></th>
                <th style="width: 10%;"><?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?></th>
                <th style="width: 10%;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $display_ingredients as $ingredient ) : ?>
                  <?php
                  $icon      = PLS_Taxonomies::icon_for_term( $ingredient->term_id );
                  $icon_id   = absint( get_term_meta( $ingredient->term_id, 'pls_ingredient_icon_id', true ) );
                  $short     = get_term_meta( $ingredient->term_id, 'pls_ingredient_short_desc', true );
                  $is_active = (int) get_term_meta( $ingredient->term_id, 'pls_ingredient_is_active', true );
                  ?>
                  <tr>
                    <td><input type="text" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][name]" value="<?php echo esc_attr( $ingredient->name ); ?>" class="regular-text" style="width: 100%;" /></td>
                    <td><code style="font-size: 11px;"><?php echo esc_html( $ingredient->slug ); ?></code></td>
                    <td>
                      <label style="display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][is_active]" value="1" <?php checked( $is_active, 1 ); ?> />
                        <?php if ( $is_active ) : ?>
                          <span class="pls-badge pls-badge--success" style="font-size: 10px;"><?php esc_html_e( 'Active', 'pls-private-label-store' ); ?></span>
                        <?php else : ?>
                          <span class="pls-badge pls-badge--info" style="font-size: 10px;"><?php esc_html_e( 'Base', 'pls-private-label-store' ); ?></span>
                        <?php endif; ?>
                      </label>
                    </td>
                    <td><input type="text" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][short_desc]" value="<?php echo esc_attr( $short ); ?>" class="regular-text" style="width: 100%;" placeholder="<?php esc_attr_e( 'Why it matters', 'pls-private-label-store' ); ?>" /></td>
                    <td>
                      <div class="pls-icon-picker" data-target="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>">
                        <input type="hidden" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][icon_id]" id="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>" value="<?php echo esc_attr( $icon_id ); ?>" />
                        <button type="button" class="button button-small pls-icon-pick"><?php esc_html_e( 'Select', 'pls-private-label-store' ); ?></button>
                        <button type="button" class="button-link-delete pls-icon-clear" style="font-size: 11px;"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
                      </div>
                    </td>
                    <td>
                      <div class="pls-icon-preview" <?php echo $icon ? '' : 'style="min-height:24px"'; ?> data-default="<?php echo esc_attr( $icon ); ?>">
                        <?php if ( $icon ) : ?>
                          <img src="<?php echo esc_url( $icon ); ?>" alt="" style="max-height:32px;" />
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this ingredient?', 'pls-private-label-store' ); ?>');">
                        <?php wp_nonce_field( 'pls_ingredient_delete' ); ?>
                        <input type="hidden" name="pls_ingredient_delete" value="1" />
                        <input type="hidden" name="ingredient_id" value="<?php echo esc_attr( $ingredient->term_id ); ?>" />
                        <button type="submit" class="button button-small button-link-delete" style="color: #b32d2e;">
                          <?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?>
                        </button>
                      </form>
                    </td>
                  </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'pls-private-label-store' ); ?></button></p>
        </form>
    <?php endif; ?>
  </div>
</div>
