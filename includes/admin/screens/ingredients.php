<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$notice      = '';
$error       = '';
$created_any = false;

if ( isset( $_POST['pls_ingredient_add'] ) && check_admin_referer( 'pls_ingredient_add' ) ) {
    $name = isset( $_POST['ingredient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ingredient_name'] ) ) : '';
    $icon = isset( $_POST['ingredient_icon'] ) ? esc_url_raw( wp_unslash( $_POST['ingredient_icon'] ) ) : '';

    if ( $name ) {
        $slug  = sanitize_title( $name );
        $maybe = term_exists( $slug, 'pls_ingredient' );
        if ( ! $maybe ) {
            $result = wp_insert_term( $name, 'pls_ingredient', array( 'slug' => $slug ) );
            if ( ! is_wp_error( $result ) ) {
                if ( $icon ) {
                    update_term_meta( $result['term_id'], 'pls_ingredient_icon', $icon );
                }
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
?>
<div class="wrap pls-wrap">
  <h1><?php esc_html_e( 'PLS – Ingredients Base', 'pls-private-label-store' ); ?></h1>
  <p class="description"><?php esc_html_e( 'Maintain a clean library of ingredients (with optional icons) to reuse across products.', 'pls-private-label-store' ); ?></p>

  <?php if ( $notice ) : ?>
      <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>
  <?php if ( $error ) : ?>
      <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
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
        <label><?php esc_html_e( 'Icon URL (optional)', 'pls-private-label-store' ); ?></label>
        <input type="url" name="ingredient_icon" class="regular-text" placeholder="https://.../icon.svg" />
      </div>
      <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Ingredient', 'pls-private-label-store' ); ?></button></p>
    </form>
  </div>

  <div class="pls-card pls-card--panel">
    <h2><?php esc_html_e( 'Bulk create missing', 'pls-private-label-store' ); ?></h2>
    <form method="post">
      <?php wp_nonce_field( 'pls_ingredient_bulk' ); ?>
      <input type="hidden" name="pls_ingredient_bulk" value="1" />
      <div class="pls-field-row">
        <label><?php esc_html_e( 'Comma separated list', 'pls-private-label-store' ); ?></label>
        <input type="text" name="bulk_ingredients" class="regular-text" placeholder="Vitamin C, Niacinamide, Retinol" />
      </div>
      <p class="submit"><button class="button">Create missing entries</button></p>
    </form>
  </div>

  <h2><?php esc_html_e( 'Existing ingredients', 'pls-private-label-store' ); ?></h2>
  <div class="pls-card-grid">
    <?php if ( empty( $ingredients ) ) : ?>
        <p class="description"><?php esc_html_e( 'Nothing yet. Start adding your ingredient library above.', 'pls-private-label-store' ); ?></p>
    <?php else : ?>
        <?php foreach ( $ingredients as $ingredient ) : ?>
            <?php $icon = PLS_Taxonomies::icon_for_term( $ingredient->term_id ); ?>
            <div class="pls-card">
              <div class="pls-card__heading">
                <strong><?php echo esc_html( $ingredient->name ); ?></strong>
                <code><?php echo esc_html( $ingredient->slug ); ?></code>
              </div>
              <?php if ( $icon ) : ?>
                  <div class="pls-chip">🖼 <?php esc_html_e( 'Icon attached', 'pls-private-label-store' ); ?></div>
              <?php else : ?>
                  <div class="pls-chip pls-chip--muted"><?php esc_html_e( 'No icon yet', 'pls-private-label-store' ); ?></div>
              <?php endif; ?>
              <?php if ( $ingredient->description ) : ?>
                  <p class="description"><?php echo esc_html( $ingredient->description ); ?></p>
              <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
