<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$notice = '';
$error = '';

// Handle Delete
if ( isset( $_POST['pls_category_delete'] ) && check_admin_referer( 'pls_category_delete' ) ) {
    $term_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
    if ( $term_id ) {
        $result = wp_delete_term( $term_id, 'product_cat' );
        if ( ! is_wp_error( $result ) && $result !== false ) {
            $notice = __( 'Category deleted.', 'pls-private-label-store' );
        } else {
            $error = __( 'Failed to delete category.', 'pls-private-label-store' );
        }
    }
}

// Handle Edit
if ( isset( $_POST['pls_category_edit'] ) && check_admin_referer( 'pls_category_edit' ) ) {
    $term_id     = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
    $name        = isset( $_POST['category_name'] ) ? sanitize_text_field( wp_unslash( $_POST['category_name'] ) ) : '';
    $description = isset( $_POST['category_desc'] ) ? wp_kses_post( wp_unslash( $_POST['category_desc'] ) ) : '';
    $meta_title  = isset( $_POST['category_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['category_meta_title'] ) ) : '';
    $meta_desc   = isset( $_POST['category_meta_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['category_meta_desc'] ) ) : '';

    if ( $term_id && $name ) {
        $result = wp_update_term( $term_id, 'product_cat', array(
            'name'        => $name,
            'description' => $description,
        ) );
        if ( ! is_wp_error( $result ) ) {
            update_term_meta( $term_id, '_pls_meta_title', $meta_title );
            update_term_meta( $term_id, '_pls_meta_desc', $meta_desc );
            $notice = __( 'Category updated.', 'pls-private-label-store' );
        } else {
            $error = $result->get_error_message();
        }
    }
}

// Handle Add
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
                $error = $created->get_error_message();
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
            <p class="description">
                <?php esc_html_e( 'Manage product categories with SEO meta fields. Categories help organize products and improve search visibility.', 'pls-private-label-store' ); ?>
                <span class="pls-help-icon" title="<?php esc_attr_e( 'Meta Title and Meta Description are used for SEO. They appear in search results and help improve visibility.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
            </p>
        </div>
    </div>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $notice ); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $error ); ?></p>
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
                    <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" class="button button-small pls-edit-category" 
                                data-id="<?php echo esc_attr( $cat->term_id ); ?>"
                                data-name="<?php echo esc_attr( $cat->name ); ?>"
                                data-description="<?php echo esc_attr( $cat->description ); ?>"
                                data-meta-title="<?php echo esc_attr( $meta_title ); ?>"
                                data-meta-desc="<?php echo esc_attr( $meta_desc ); ?>">
                            <?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?>
                        </button>
                        <a href="<?php echo esc_url( add_query_arg( array(
                            'pls_preview' => '1',
                            'category_id' => $cat->term_id,
                            'pls_preview_nonce' => wp_create_nonce( 'pls_admin_nonce' ),
                        ), home_url() ) ); ?>" 
                           class="button button-small" 
                           target="_blank">
                            <?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?>
                        </a>
                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this category?', 'pls-private-label-store' ); ?>');">
                            <?php wp_nonce_field( 'pls_category_delete' ); ?>
                            <input type="hidden" name="pls_category_delete" value="1" />
                            <input type="hidden" name="category_id" value="<?php echo esc_attr( $cat->term_id ); ?>" />
                            <button type="submit" class="button button-small button-link-delete" style="color: #b32d2e;">
                                <?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="pls-category-edit-modal" class="pls-modal" style="display: none;">
    <div class="pls-modal__dialog" style="max-width: 600px;">
        <div class="pls-modal__head">
            <h2 style="margin: 0;"><?php esc_html_e( 'Edit Category', 'pls-private-label-store' ); ?></h2>
            <button type="button" class="pls-modal__close pls-close-category-modal">&times;</button>
        </div>
        <div class="pls-modal__content" style="padding: 20px;">
            <form method="post" id="pls-category-edit-form">
                <?php wp_nonce_field( 'pls_category_edit' ); ?>
                <input type="hidden" name="pls_category_edit" value="1" />
                <input type="hidden" name="category_id" id="edit_category_id" value="" />
                
                <div class="pls-input-group" style="margin-bottom: 16px;">
                    <label for="edit_category_name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label>
                    <input type="text" id="edit_category_name" name="category_name" class="pls-input" required style="width: 100%;" />
                </div>
                
                <div class="pls-input-group" style="margin-bottom: 16px;">
                    <label for="edit_category_desc"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                    <textarea id="edit_category_desc" name="category_desc" rows="3" class="pls-rich-textarea" style="width: 100%;"></textarea>
                </div>
                
                <div class="pls-input-group" style="margin-bottom: 16px;">
                    <label for="edit_category_meta_title"><?php esc_html_e( 'Meta Title', 'pls-private-label-store' ); ?></label>
                    <input type="text" id="edit_category_meta_title" name="category_meta_title" class="pls-input" style="width: 100%;" />
                </div>
                
                <div class="pls-input-group" style="margin-bottom: 16px;">
                    <label for="edit_category_meta_desc"><?php esc_html_e( 'Meta Description', 'pls-private-label-store' ); ?></label>
                    <textarea id="edit_category_meta_desc" name="category_meta_desc" rows="3" class="pls-rich-textarea" style="width: 100%;"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="button pls-close-category-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'pls-private-label-store' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Open edit modal
    $('.pls-edit-category').on('click', function() {
        var $btn = $(this);
        $('#edit_category_id').val($btn.data('id'));
        $('#edit_category_name').val($btn.data('name'));
        $('#edit_category_desc').val($btn.data('description'));
        $('#edit_category_meta_title').val($btn.data('meta-title'));
        $('#edit_category_meta_desc').val($btn.data('meta-desc'));
        $('#pls-category-edit-modal').show();
    });
    
    // Close modal
    $('.pls-close-category-modal, .pls-modal').on('click', function(e) {
        if (e.target === this) {
            $('#pls-category-edit-modal').hide();
        }
    });
    
    // Prevent modal content click from closing
    $('.pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
