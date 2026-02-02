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
    $parent_id   = isset( $_POST['category_parent'] ) ? absint( $_POST['category_parent'] ) : 0;
    $faq_json    = isset( $_POST['category_faq'] ) ? wp_unslash( $_POST['category_faq'] ) : '';
    $custom_order = isset( $_POST['category_custom_order'] ) ? absint( $_POST['category_custom_order'] ) : 0;

    if ( $term_id && $name ) {
        $result = wp_update_term( $term_id, 'product_cat', array(
            'name'        => $name,
            'description' => $description,
            'parent'      => $parent_id,
        ) );
        if ( ! is_wp_error( $result ) ) {
            update_term_meta( $term_id, '_pls_meta_title', $meta_title );
            update_term_meta( $term_id, '_pls_meta_desc', $meta_desc );
            update_term_meta( $term_id, '_pls_faq_json', $faq_json );
            update_term_meta( $term_id, '_pls_custom_order', $custom_order );
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
    $parent_id   = isset( $_POST['category_parent'] ) ? absint( $_POST['category_parent'] ) : 0;
    $is_parent   = isset( $_POST['is_parent_category'] ) && $_POST['is_parent_category'] === '1';
    $faq_json    = isset( $_POST['category_faq'] ) ? wp_unslash( $_POST['category_faq'] ) : '';
    $custom_order = isset( $_POST['category_custom_order'] ) ? absint( $_POST['category_custom_order'] ) : 0;

    if ( $name ) {
        $slug  = sanitize_title( $name );
        $maybe = term_exists( $slug, 'product_cat' );
        if ( $maybe && is_array( $maybe ) ) {
            $term_id = $maybe['term_id'];
        } elseif ( $maybe && is_object( $maybe ) ) {
            $term_id = $maybe->term_id;
        } else {
            $insert_args = array( 
                'slug' => $slug, 
                'description' => $description 
            );
            // If not a parent category, assign to selected parent
            if ( ! $is_parent && $parent_id > 0 ) {
                $insert_args['parent'] = $parent_id;
            }
            $created = wp_insert_term( $name, 'product_cat', $insert_args );
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
            update_term_meta( $term_id, '_pls_faq_json', $faq_json );
            update_term_meta( $term_id, '_pls_custom_order', $custom_order );
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

// Get parent categories for dropdown
$parent_categories = array_filter(
    $categories,
    function( $term ) {
        return $term->parent === 0;
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
                    <label for="category_name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?> <span class="pls-required-indicator">*</span></label>
                    <input type="text" id="category_name" name="category_name" class="pls-input" placeholder="e.g. Serums" required />
                </div>
                <div class="pls-input-group">
                    <label><?php esc_html_e( 'Category Type', 'pls-private-label-store' ); ?></label>
                    <div style="display: flex; gap: 16px; margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="is_parent_category" value="1" id="is_parent_yes" checked />
                            <?php esc_html_e( 'Parent Category', 'pls-private-label-store' ); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="is_parent_category" value="0" id="is_parent_no" />
                            <?php esc_html_e( 'Child Category', 'pls-private-label-store' ); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="pls-field-grid" id="parent-category-select" style="display: none;">
                <div class="pls-input-group">
                    <label for="category_parent"><?php esc_html_e( 'Parent Category', 'pls-private-label-store' ); ?></label>
                    <select id="category_parent" name="category_parent" class="pls-select">
                        <option value="0"><?php esc_html_e( '— Select Parent —', 'pls-private-label-store' ); ?></option>
                        <?php foreach ( $parent_categories as $parent_cat ) : ?>
                            <option value="<?php echo esc_attr( $parent_cat->term_id ); ?>"><?php echo esc_html( $parent_cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pls-input-group">
                    <label for="category_custom_order"><?php esc_html_e( 'Custom Order', 'pls-private-label-store' ); ?></label>
                    <input type="number" id="category_custom_order" name="category_custom_order" class="pls-input" placeholder="0" min="0" value="0" />
                    <span class="pls-field-hint"><?php esc_html_e( 'Lower numbers appear first. Default is 0.', 'pls-private-label-store' ); ?></span>
                </div>
            </div>

            <div class="pls-field-grid">
                <div class="pls-input-group">
                    <label for="category_meta_title">
                        <?php esc_html_e( 'Meta Title', 'pls-private-label-store' ); ?>
                        <span class="pls-char-count" id="meta_title_count">0/60</span>
                    </label>
                    <input type="text" id="category_meta_title" name="category_meta_title" class="pls-input pls-meta-input" data-maxlength="60" data-counter="meta_title_count" placeholder="e.g. Serums – Private Label Skincare" maxlength="60" />
                    <span class="pls-field-hint"><?php esc_html_e( 'Recommended: 50-60 characters for optimal SEO.', 'pls-private-label-store' ); ?></span>
                </div>
                <div class="pls-input-group">
                    <label for="category_meta_desc">
                        <?php esc_html_e( 'Meta Description', 'pls-private-label-store' ); ?>
                        <span class="pls-char-count" id="meta_desc_count">0/160</span>
                    </label>
                    <textarea id="category_meta_desc" name="category_meta_desc" rows="3" class="pls-rich-textarea pls-meta-input" data-maxlength="160" data-counter="meta_desc_count" placeholder="e.g. Premium private label serums with customizable formulations..." maxlength="160"></textarea>
                    <span class="pls-field-hint"><?php esc_html_e( 'Recommended: 150-160 characters for optimal SEO.', 'pls-private-label-store' ); ?></span>
                </div>
            </div>
            
            <div class="pls-field-grid">
                <div class="pls-input-group">
                    <label for="category_desc"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                    <textarea id="category_desc" name="category_desc" rows="3" class="pls-rich-textarea" placeholder="Short marketing blurb for the category page..."></textarea>
                </div>
                <div class="pls-input-group">
                    <label for="category_faq"><?php esc_html_e( 'FAQ (JSON-LD Schema)', 'pls-private-label-store' ); ?> <span class="pls-help-icon" title="<?php esc_attr_e( 'Add FAQ entries in JSON format. These will be injected into JSON-LD schema for SEO. Format: [{"question":"Q1","answer":"A1"}]', 'pls-private-label-store' ); ?>">ⓘ</span></label>
                    <textarea id="category_faq" name="category_faq" rows="3" class="pls-rich-textarea" placeholder='[{"question":"What is private labeling?","answer":"Private labeling allows you to..."}]'></textarea>
                    <span class="pls-field-hint"><?php esc_html_e( 'JSON array of FAQ items for rich snippets.', 'pls-private-label-store' ); ?></span>
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
    
    <!-- Parent Categories -->
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 12px;"><?php esc_html_e( 'Parent Categories', 'pls-private-label-store' ); ?></h3>
        <div class="pls-card-grid">
            <?php 
            $has_parent_cats = false;
            foreach ( $categories as $cat ) : 
                if ( $cat->parent !== 0 ) continue;
                $has_parent_cats = true;
                $meta_title = get_term_meta( $cat->term_id, '_pls_meta_title', true );
                $meta_desc  = get_term_meta( $cat->term_id, '_pls_meta_desc', true );
                $faq_json   = get_term_meta( $cat->term_id, '_pls_faq_json', true );
                $custom_order = get_term_meta( $cat->term_id, '_pls_custom_order', true );
                $child_count = count( array_filter( $categories, function( $c ) use ( $cat ) { return $c->parent === (int) $cat->term_id; } ) );
            ?>
                <div class="pls-card pls-card--interactive" style="border-left: 4px solid var(--pls-accent);">
                    <div class="pls-card__heading">
                        <strong style="font-size: 16px; font-weight: 600;"><?php echo esc_html( $cat->name ); ?></strong>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <span class="pls-badge pls-badge--info"><?php echo esc_html( $child_count ); ?> <?php esc_html_e( 'children', 'pls-private-label-store' ); ?></span>
                            <code style="background: var(--pls-gray-100); color: var(--pls-gray-600); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-family: monospace;"><?php echo esc_html( $cat->slug ); ?></code>
                        </div>
                    </div>
                    <?php if ( $cat->description ) : ?>
                        <p style="margin: 8px 0; color: var(--pls-gray-600);"><?php echo esc_html( wp_trim_words( $cat->description, 15 ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( $meta_title ) : ?>
                        <div class="pls-chip" style="margin-top: 8px;">
                            <strong style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px;">SEO:</strong>
                            <?php echo esc_html( wp_trim_words( $meta_title, 8 ) ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( $custom_order ) : ?>
                        <span class="pls-badge" style="margin-top: 8px;"><?php esc_html_e( 'Order:', 'pls-private-label-store' ); ?> <?php echo esc_html( $custom_order ); ?></span>
                    <?php endif; ?>
                    <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" class="button button-small pls-edit-category" 
                                data-id="<?php echo esc_attr( $cat->term_id ); ?>"
                                data-name="<?php echo esc_attr( $cat->name ); ?>"
                                data-description="<?php echo esc_attr( $cat->description ); ?>"
                                data-meta-title="<?php echo esc_attr( $meta_title ); ?>"
                                data-meta-desc="<?php echo esc_attr( $meta_desc ); ?>"
                                data-parent="0"
                                data-faq="<?php echo esc_attr( $faq_json ); ?>"
                                data-custom-order="<?php echo esc_attr( $custom_order ); ?>">
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
            <?php if ( ! $has_parent_cats ) : ?>
                <div class="pls-card" style="text-align: center; padding: 24px; grid-column: 1 / -1;">
                    <p style="margin: 0; color: var(--pls-gray-500);"><?php esc_html_e( 'No parent categories yet.', 'pls-private-label-store' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Child Categories -->
    <div>
        <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 12px;"><?php esc_html_e( 'Child Categories', 'pls-private-label-store' ); ?></h3>
        <div class="pls-card-grid">
            <?php 
            $has_child_cats = false;
            foreach ( $categories as $cat ) : 
                if ( $cat->parent === 0 ) continue;
                $has_child_cats = true;
                $meta_title = get_term_meta( $cat->term_id, '_pls_meta_title', true );
                $meta_desc  = get_term_meta( $cat->term_id, '_pls_meta_desc', true );
                $faq_json   = get_term_meta( $cat->term_id, '_pls_faq_json', true );
                $custom_order = get_term_meta( $cat->term_id, '_pls_custom_order', true );
                $parent_term = get_term( $cat->parent, 'product_cat' );
                $parent_name = ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->name : '';
            ?>
                <div class="pls-card pls-card--interactive">
                    <div class="pls-card__heading">
                        <strong style="font-size: 16px; font-weight: 600;"><?php echo esc_html( $cat->name ); ?></strong>
                        <code style="background: var(--pls-gray-100); color: var(--pls-gray-600); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-family: monospace;"><?php echo esc_html( $cat->slug ); ?></code>
                    </div>
                    <?php if ( $parent_name ) : ?>
                        <div style="margin: 4px 0;">
                            <span class="pls-badge" style="background: var(--pls-accent-light); color: var(--pls-accent);">↳ <?php echo esc_html( $parent_name ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $cat->description ) : ?>
                        <p style="margin: 8px 0; color: var(--pls-gray-600);"><?php echo esc_html( wp_trim_words( $cat->description, 12 ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( $meta_title ) : ?>
                        <div class="pls-chip" style="margin-top: 8px;">
                            <strong style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px;">SEO:</strong>
                            <?php echo esc_html( wp_trim_words( $meta_title, 6 ) ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( $custom_order ) : ?>
                        <span class="pls-badge" style="margin-top: 8px;"><?php esc_html_e( 'Order:', 'pls-private-label-store' ); ?> <?php echo esc_html( $custom_order ); ?></span>
                    <?php endif; ?>
                    <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" class="button button-small pls-edit-category" 
                                data-id="<?php echo esc_attr( $cat->term_id ); ?>"
                                data-name="<?php echo esc_attr( $cat->name ); ?>"
                                data-description="<?php echo esc_attr( $cat->description ); ?>"
                                data-meta-title="<?php echo esc_attr( $meta_title ); ?>"
                                data-meta-desc="<?php echo esc_attr( $meta_desc ); ?>"
                                data-parent="<?php echo esc_attr( $cat->parent ); ?>"
                                data-faq="<?php echo esc_attr( $faq_json ); ?>"
                                data-custom-order="<?php echo esc_attr( $custom_order ); ?>">
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
            <?php if ( ! $has_child_cats ) : ?>
                <div class="pls-card" style="text-align: center; padding: 24px; grid-column: 1 / -1;">
                    <p style="margin: 0; color: var(--pls-gray-500);"><?php esc_html_e( 'No child categories yet. Create a child category by selecting a parent above.', 'pls-private-label-store' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Shortcode Info -->
    <div class="pls-card" style="margin-top: 24px; background: var(--pls-gray-50);">
        <h3 style="margin-top: 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Shortcodes', 'pls-private-label-store' ); ?></h3>
        <p class="description"><?php esc_html_e( 'Use these shortcodes to display categories on your pages:', 'pls-private-label-store' ); ?></p>
        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
            <div>
                <code style="background: #fff; padding: 8px 12px; border-radius: 4px; display: inline-block;">[pls_single_category]</code>
                <span style="margin-left: 8px; color: var(--pls-gray-600);"><?php esc_html_e( 'Display category archive with products (auto-detects current category)', 'pls-private-label-store' ); ?></span>
            </div>
            <div>
                <code style="background: #fff; padding: 8px 12px; border-radius: 4px; display: inline-block;">[pls_category_archive category_id="123"]</code>
                <span style="margin-left: 8px; color: var(--pls-gray-600);"><?php esc_html_e( 'Display specific category with all data and product loop', 'pls-private-label-store' ); ?></span>
            </div>
            <div>
                <code style="background: #fff; padding: 8px 12px; border-radius: 4px; display: inline-block;">[pls_shop_page]</code>
                <span style="margin-left: 8px; color: var(--pls-gray-600);"><?php esc_html_e( 'Display all products in shop layout', 'pls-private-label-store' ); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal (Fullscreen) -->
<div id="pls-category-edit-modal" class="pls-modal pls-modal--fullscreen" style="display: none;">
    <div class="pls-modal__dialog" style="width: 100%; max-width: 100%; height: 100%; max-height: 100%; margin: 0; border-radius: 0;">
        <div class="pls-modal__head" style="border-bottom: 1px solid var(--pls-gray-200); padding: 16px 24px;">
            <h2 style="margin: 0; font-size: 20px;"><?php esc_html_e( 'Edit Category', 'pls-private-label-store' ); ?></h2>
            <button type="button" class="pls-modal__close pls-close-category-modal">&times;</button>
        </div>
        <div class="pls-modal__content" style="padding: 24px; overflow-y: auto; height: calc(100vh - 140px);">
            <form method="post" id="pls-category-edit-form">
                <?php wp_nonce_field( 'pls_category_edit' ); ?>
                <input type="hidden" name="pls_category_edit" value="1" />
                <input type="hidden" name="category_id" id="edit_category_id" value="" />
                
                <div style="max-width: 1200px; margin: 0 auto;">
                    <!-- Basic Info Section -->
                    <div class="pls-modal__section">
                        <h3 style="margin-top: 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Basic Information', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-field-grid">
                            <div class="pls-input-group">
                                <label for="edit_category_name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?> <span class="pls-required-indicator">*</span></label>
                                <input type="text" id="edit_category_name" name="category_name" class="pls-input" required style="width: 100%;" placeholder="e.g. Serums" />
                            </div>
                            <div class="pls-input-group">
                                <label for="edit_category_parent"><?php esc_html_e( 'Parent Category', 'pls-private-label-store' ); ?></label>
                                <select id="edit_category_parent" name="category_parent" class="pls-select" style="width: 100%;">
                                    <option value="0"><?php esc_html_e( '— None (Parent Category) —', 'pls-private-label-store' ); ?></option>
                                    <?php foreach ( $parent_categories as $parent_cat ) : ?>
                                        <option value="<?php echo esc_attr( $parent_cat->term_id ); ?>"><?php echo esc_html( $parent_cat->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="pls-field-grid">
                            <div class="pls-input-group">
                                <label for="edit_category_desc"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                                <textarea id="edit_category_desc" name="category_desc" rows="4" class="pls-rich-textarea" style="width: 100%;" placeholder="Marketing description for the category page..."></textarea>
                            </div>
                            <div class="pls-input-group">
                                <label for="edit_category_custom_order"><?php esc_html_e( 'Custom Order', 'pls-private-label-store' ); ?></label>
                                <input type="number" id="edit_category_custom_order" name="category_custom_order" class="pls-input" style="width: 100%;" min="0" placeholder="0" />
                                <span class="pls-field-hint"><?php esc_html_e( 'Lower numbers appear first in listings. Default is 0.', 'pls-private-label-store' ); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Section -->
                    <div class="pls-modal__section">
                        <h3 style="margin-top: 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'SEO Settings', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-field-grid">
                            <div class="pls-input-group">
                                <label for="edit_category_meta_title">
                                    <?php esc_html_e( 'Meta Title', 'pls-private-label-store' ); ?>
                                    <span class="pls-char-count" id="edit_meta_title_count">0/60</span>
                                </label>
                                <input type="text" id="edit_category_meta_title" name="category_meta_title" class="pls-input pls-meta-input" data-maxlength="60" data-counter="edit_meta_title_count" style="width: 100%;" maxlength="60" placeholder="e.g. Premium Serums - Private Label Skincare" />
                                <span class="pls-field-hint"><?php esc_html_e( 'Recommended: 50-60 characters. Appears in search results.', 'pls-private-label-store' ); ?></span>
                            </div>
                            <div class="pls-input-group">
                                <label for="edit_category_meta_desc">
                                    <?php esc_html_e( 'Meta Description', 'pls-private-label-store' ); ?>
                                    <span class="pls-char-count" id="edit_meta_desc_count">0/160</span>
                                </label>
                                <textarea id="edit_category_meta_desc" name="category_meta_desc" rows="3" class="pls-rich-textarea pls-meta-input" data-maxlength="160" data-counter="edit_meta_desc_count" style="width: 100%;" maxlength="160" placeholder="e.g. Discover premium private label serums with customizable formulations..."></textarea>
                                <span class="pls-field-hint"><?php esc_html_e( 'Recommended: 150-160 characters. Appears below title in search results.', 'pls-private-label-store' ); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Schema Section -->
                    <div class="pls-modal__section">
                        <h3 style="margin-top: 0; font-size: 16px; font-weight: 600;">
                            <?php esc_html_e( 'FAQ Schema (JSON-LD)', 'pls-private-label-store' ); ?>
                            <span class="pls-help-icon" title="<?php esc_attr_e( 'FAQ entries are injected into JSON-LD structured data for rich snippets in Google search results.', 'pls-private-label-store' ); ?>">ⓘ</span>
                        </h3>
                        <div class="pls-input-group">
                            <label for="edit_category_faq"><?php esc_html_e( 'FAQ JSON', 'pls-private-label-store' ); ?></label>
                            <textarea id="edit_category_faq" name="category_faq" rows="6" class="pls-rich-textarea" style="width: 100%; font-family: monospace;" placeholder='[
  {"question": "What is private label skincare?", "answer": "Private label skincare allows brands to sell products manufactured by another company under their own brand name."},
  {"question": "What is the minimum order quantity?", "answer": "Our minimum order quantity starts at 50 units per product."}
]'></textarea>
                            <span class="pls-field-hint"><?php esc_html_e( 'Format: JSON array of objects with "question" and "answer" keys. These appear as FAQ rich snippets in search results.', 'pls-private-label-store' ); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="pls-modal__footer" style="padding: 16px 24px; border-top: 1px solid var(--pls-gray-200); margin-top: 24px;">
                    <button type="button" class="button pls-close-category-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'pls-private-label-store' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // ============================================
    // Character Counter for Meta Fields
    // ============================================
    function updateCharCount(input) {
        var $input = $(input);
        var maxLength = parseInt($input.data('maxlength')) || 160;
        var counterId = $input.data('counter');
        var currentLength = $input.val().length;
        var $counter = $('#' + counterId);
        
        $counter.text(currentLength + '/' + maxLength);
        
        // Color coding
        if (currentLength >= maxLength * 0.9) {
            $counter.css('color', 'var(--pls-error)');
        } else if (currentLength >= maxLength * 0.7) {
            $counter.css('color', 'var(--pls-warning)');
        } else {
            $counter.css('color', 'var(--pls-success)');
        }
    }
    
    // Initialize counters on page load
    $('.pls-meta-input').each(function() {
        updateCharCount(this);
    });
    
    // Live update on input
    $(document).on('input keyup', '.pls-meta-input', function() {
        updateCharCount(this);
    });
    
    // ============================================
    // Parent/Child Category Toggle
    // ============================================
    $('input[name="is_parent_category"]').on('change', function() {
        var isChild = $(this).val() === '0';
        if (isChild) {
            $('#parent-category-select').slideDown(200);
        } else {
            $('#parent-category-select').slideUp(200);
            $('#category_parent').val('0');
        }
    });
    
    // Initialize on page load
    if ($('#is_parent_no').is(':checked')) {
        $('#parent-category-select').show();
    }
    
    // ============================================
    // Edit Modal
    // ============================================
    $('.pls-edit-category').on('click', function() {
        var $btn = $(this);
        $('#edit_category_id').val($btn.data('id'));
        $('#edit_category_name').val($btn.data('name'));
        $('#edit_category_desc').val($btn.data('description') || '');
        $('#edit_category_meta_title').val($btn.data('meta-title') || '');
        $('#edit_category_meta_desc').val($btn.data('meta-desc') || '');
        $('#edit_category_parent').val($btn.data('parent') || 0);
        $('#edit_category_faq').val($btn.data('faq') || '');
        $('#edit_category_custom_order').val($btn.data('custom-order') || 0);
        
        // Update character counters
        updateCharCount($('#edit_category_meta_title')[0]);
        updateCharCount($('#edit_category_meta_desc')[0]);
        
        $('#pls-category-edit-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });
    
    // Close modal
    $('.pls-close-category-modal').on('click', function(e) {
        e.preventDefault();
        $('#pls-category-edit-modal').fadeOut(200);
        $('body').removeClass('pls-modal-open');
    });
    
    // Close on backdrop click
    $('#pls-category-edit-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
            $('body').removeClass('pls-modal-open');
        }
    });
    
    // Prevent modal content click from closing
    $('#pls-category-edit-modal .pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Close on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#pls-category-edit-modal').is(':visible')) {
            $('#pls-category-edit-modal').fadeOut(200);
            $('body').removeClass('pls-modal-open');
        }
    });
});
</script>

<style>
/* Character count indicator */
.pls-char-count {
    float: right;
    font-size: 12px;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 4px;
    background: var(--pls-gray-100);
}

/* Fullscreen modal */
.pls-modal--fullscreen {
    padding: 0 !important;
}

.pls-modal--fullscreen .pls-modal__dialog {
    background: #fff !important;
}

/* Field hint styling */
.pls-field-hint {
    display: block;
    font-size: 12px;
    color: var(--pls-gray-500);
    margin-top: 4px;
}

/* Required indicator */
.pls-required-indicator {
    color: var(--pls-error);
    font-weight: bold;
}
</style>
