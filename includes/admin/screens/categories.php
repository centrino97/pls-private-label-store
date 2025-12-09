<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$notice = '';

if ( isset( $_POST['pls_category_add'] ) && check_admin_referer( 'pls_category_add' ) ) {
    $name        = isset( $_POST['category_name'] ) ? sanitize_text_field( wp_unslash( $_POST['category_name'] ) ) : '';
    $description = isset( $_POST['category_desc'] ) ? wp_kses_post( wp_unslash( $_POST['category_desc'] ) ) : '';
    $meta_title  = isset( $_POST['category_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['category_meta_title'] ) ) : '';
    $meta_desc   = isset( $_POST['category_meta_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['category_meta_desc'] ) ) : '';

    if ( $name ) {
        $slug  = sanitize_title( $name );
        $maybe = term_exists( $slug, 'product_cat' );
        if ( $maybe && is_array( $maybe ) ) {
            $term_id = $maybe['term_id'];
        } elseif ( $maybe && is_object( $maybe ) ) {
            $term_id = $maybe->term_id;
        } else {
            $created = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'description' => $description ) );
            if ( ! is_wp_error( $created ) ) {
                $term_id = $created['term_id'];
            } else {
                $term_id = 0;
            }
        }

        if ( ! empty( $term_id ) ) {
            update_term_meta( $term_id, '_pls_meta_title', $meta_title );
            update_term_meta( $term_id, '_pls_meta_desc', $meta_desc );
            $notice = __( 'Category saved.', 'pls-private-label-store' );
        }
    }
}

$categories = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $categories ) ) {
    $categories = array();
}

$categories = array_filter(
    $categories,
    function( $term ) {
        return 'uncategorized' !== $term->slug;
    }
);
?>
<div class="wrap pls-wrap">
  <h1><?php esc_html_e( 'PLS – Categories', 'pls-private-label-store' ); ?></h1>
  <p class="description"><?php esc_html_e( 'Add fresh PLS categories with SEO meta without touching the default WooCommerce taxonomy screens.', 'pls-private-label-store' ); ?></p>

  <?php if ( $notice ) : ?>
      <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>

  <div class="pls-card pls-card--panel">
    <h2><?php esc_html_e( 'Create category', 'pls-private-label-store' ); ?></h2>
    <form method="post">
      <?php wp_nonce_field( 'pls_category_add' ); ?>
      <input type="hidden" name="pls_category_add" value="1" />
      <div class="pls-field-grid">
        <div>
          <label><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label>
          <input type="text" name="category_name" class="regular-text" placeholder="Serums" required />
        </div>
        <div>
          <label><?php esc_html_e( 'Meta Title', 'pls-private-label-store' ); ?></label>
          <input type="text" name="category_meta_title" class="regular-text" placeholder="Serums – Private Label" />
        </div>
      </div>
      <div class="pls-field-grid">
        <div>
          <label><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
          <textarea name="category_desc" rows="3" class="large-text" placeholder="Short marketing blurb..."></textarea>
        </div>
        <div>
          <label><?php esc_html_e( 'Meta Description', 'pls-private-label-store' ); ?></label>
          <textarea name="category_meta_desc" rows="3" class="large-text" placeholder="SEO summary..."></textarea>
        </div>
      </div>
      <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Category', 'pls-private-label-store' ); ?></button></p>
    </form>
  </div>

  <h2><?php esc_html_e( 'Categories (Uncategorized hidden)', 'pls-private-label-store' ); ?></h2>
  <div class="pls-card-grid">
    <?php if ( empty( $categories ) ) : ?>
        <p class="description"><?php esc_html_e( 'No categories yet.', 'pls-private-label-store' ); ?></p>
    <?php else : ?>
        <?php foreach ( $categories as $cat ) : ?>
            <?php $meta_title = get_term_meta( $cat->term_id, '_pls_meta_title', true ); ?>
            <?php $meta_desc  = get_term_meta( $cat->term_id, '_pls_meta_desc', true ); ?>
            <div class="pls-card">
              <div class="pls-card__heading">
                <strong><?php echo esc_html( $cat->name ); ?></strong>
                <code><?php echo esc_html( $cat->slug ); ?></code>
              </div>
              <?php if ( $meta_title ) : ?>
                  <div class="pls-chip">SEO: <?php echo esc_html( $meta_title ); ?></div>
              <?php endif; ?>
              <?php if ( $meta_desc ) : ?>
                  <p class="description">Meta: <?php echo esc_html( $meta_desc ); ?></p>
              <?php endif; ?>
              <?php if ( $cat->description ) : ?>
                  <p><?php echo esc_html( $cat->description ); ?></p>
              <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
