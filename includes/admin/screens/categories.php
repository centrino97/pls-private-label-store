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
<div class="wrap pls-wrap pls-page-categories">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Product Categories', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Manage product categories with SEO meta fields.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $notice ); ?></p>
        </div>
    <?php endif; ?>

    <div class="pls-card" style="margin-bottom: 24px;">
        <h2 style="margin-top: 0; font-size: 18px; font-weight: 600;"><?php esc_html_e( 'Create Category', 'pls-private-label-store' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'pls_category_add' ); ?>
            <input type="hidden" name="pls_category_add" value="1" />
            <div class="pls-field-grid">
                <div class="pls-input-group">
                    <label for="category_name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label>
                    <input type="text" id="category_name" name="category_name" class="pls-input" placeholder="Serums" required />
                </div>
                <div class="pls-input-group">
                    <label for="category_meta_title"><?php esc_html_e( 'Meta Title', 'pls-private-label-store' ); ?></label>
                    <input type="text" id="category_meta_title" name="category_meta_title" class="pls-input" placeholder="Serums – Private Label" />
                </div>
            </div>
            <div class="pls-field-grid">
                <div class="pls-input-group">
                    <label for="category_desc"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                    <textarea id="category_desc" name="category_desc" rows="3" class="pls-rich-textarea" placeholder="Short marketing blurb..."></textarea>
                </div>
                <div class="pls-input-group">
                    <label for="category_meta_desc"><?php esc_html_e( 'Meta Description', 'pls-private-label-store' ); ?></label>
                    <textarea id="category_meta_desc" name="category_meta_desc" rows="3" class="pls-rich-textarea" placeholder="SEO summary..."></textarea>
                </div>
            </div>
            <p class="submit" style="margin-top: 16px;">
                <button type="submit" class="button button-primary pls-btn--primary"><?php esc_html_e( 'Save Category', 'pls-private-label-store' ); ?></button>
            </p>
        </form>
    </div>

    <div style="margin-bottom: 16px;">
        <h2 style="font-size: 18px; font-weight: 600; margin: 0;"><?php esc_html_e( 'All Categories', 'pls-private-label-store' ); ?></h2>
        <p class="description" style="margin: 4px 0 0;"><?php esc_html_e( 'Uncategorized category is hidden from this list.', 'pls-private-label-store' ); ?></p>
    </div>
    
    <div class="pls-card-grid">
        <?php if ( empty( $categories ) ) : ?>
            <div class="pls-card" style="text-align: center; padding: 48px 24px; grid-column: 1 / -1;">
                <div style="font-size: 48px; color: var(--pls-gray-300); margin-bottom: 16px;">📁</div>
                <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: var(--pls-gray-900);"><?php esc_html_e( 'No categories yet', 'pls-private-label-store' ); ?></h2>
                <p style="margin: 0; color: var(--pls-gray-500);"><?php esc_html_e( 'Create your first category to organize products.', 'pls-private-label-store' ); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ( $categories as $cat ) : ?>
                <?php $meta_title = get_term_meta( $cat->term_id, '_pls_meta_title', true ); ?>
                <?php $meta_desc  = get_term_meta( $cat->term_id, '_pls_meta_desc', true ); ?>
                <div class="pls-card pls-card--interactive">
                    <div class="pls-card__heading">
                        <strong style="font-size: 16px; font-weight: 600;"><?php echo esc_html( $cat->name ); ?></strong>
                        <code style="background: var(--pls-gray-100); color: var(--pls-gray-600); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-family: monospace;"><?php echo esc_html( $cat->slug ); ?></code>
                    </div>
                    <?php if ( $cat->description ) : ?>
                        <p style="margin: 8px 0; color: var(--pls-gray-600);"><?php echo esc_html( $cat->description ); ?></p>
                    <?php endif; ?>
                    <?php if ( $meta_title ) : ?>
                        <div class="pls-chip" style="margin-top: 8px;">
                            <strong style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px;">SEO:</strong>
                            <?php echo esc_html( $meta_title ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( $meta_desc ) : ?>
                        <p class="description" style="margin-top: 8px; font-size: 12px; color: var(--pls-gray-500);">
                            <?php echo esc_html( wp_trim_words( $meta_desc, 15 ) ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
