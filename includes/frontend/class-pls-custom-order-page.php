<?php
/**
 * Frontend custom order form page.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Custom_Order_Page {

    public static function init() {
        add_shortcode( 'pls_custom_order_form', array( __CLASS__, 'render_form' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_pls_submit_custom_order', array( __CLASS__, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_pls_submit_custom_order', array( __CLASS__, 'handle_submission' ) );
    }

    /**
     * Enqueue frontend assets.
     */
    public static function enqueue_assets() {
        if ( ! is_page( 'custom-order' ) ) {
            return;
        }

        wp_enqueue_style(
            'pls-custom-order',
            PLS_PLS_URL . 'assets/css/custom-order.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_enqueue_script(
            'pls-custom-order',
            PLS_PLS_URL . 'assets/js/custom-order.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        wp_localize_script(
            'pls-custom-order',
            'PLS_CustomOrder',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'pls_custom_order_nonce' ),
            )
        );
    }

    /**
     * Render the custom order form.
     */
    public static function render_form() {
        $categories = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        ob_start();
        ?>
        <div class="pls-custom-order-form">
            <div class="pls-custom-order-form__container">
                <h2><?php esc_html_e( 'Request a Custom Product', 'pls-private-label-store' ); ?></h2>
                <p class="pls-custom-order-form__description">
                    <?php esc_html_e( 'Tell us about your custom product needs. We\'ll get back to you shortly.', 'pls-private-label-store' ); ?>
                </p>

                <form id="pls-custom-order-form" class="pls-custom-order-form__form">
                    <div class="pls-custom-order-form__field">
                        <label for="pls-contact-name">
                            <?php esc_html_e( 'Your Name', 'pls-private-label-store' ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="pls-contact-name" name="contact_name" required />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-contact-email">
                            <?php esc_html_e( 'Your Email', 'pls-private-label-store' ); ?> <span class="required">*</span>
                        </label>
                        <input type="email" id="pls-contact-email" name="contact_email" required />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-contact-phone">
                            <?php esc_html_e( 'Phone Number', 'pls-private-label-store' ); ?>
                        </label>
                        <input type="tel" id="pls-contact-phone" name="contact_phone" />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-company-name">
                            <?php esc_html_e( 'Company Name', 'pls-private-label-store' ); ?>
                        </label>
                        <input type="text" id="pls-company-name" name="company_name" />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-product-category">
                            <?php esc_html_e( 'Product Category', 'pls-private-label-store' ); ?> <span class="required">*</span>
                        </label>
                        <select id="pls-product-category" name="category_id" required>
                            <option value=""><?php esc_html_e( 'Select a category', 'pls-private-label-store' ); ?></option>
                            <?php foreach ( $categories as $category ) : ?>
                                <?php if ( 'uncategorized' !== $category->slug ) : ?>
                                    <option value="<?php echo esc_attr( $category->term_id ); ?>">
                                        <?php echo esc_html( $category->name ); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <option value="other"><?php esc_html_e( 'Other', 'pls-private-label-store' ); ?></option>
                        </select>
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-quantity-needed">
                            <?php esc_html_e( 'Quantity Needed', 'pls-private-label-store' ); ?>
                        </label>
                        <input type="number" id="pls-quantity-needed" name="quantity_needed" min="1" />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-budget">
                            <?php esc_html_e( 'Budget (USD)', 'pls-private-label-store' ); ?>
                        </label>
                        <input type="number" id="pls-budget" name="budget" step="0.01" min="0" />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-timeline">
                            <?php esc_html_e( 'Timeline', 'pls-private-label-store' ); ?>
                        </label>
                        <input type="text" id="pls-timeline" name="timeline" placeholder="<?php esc_attr_e( 'e.g., 4-6 weeks', 'pls-private-label-store' ); ?>" />
                    </div>

                    <div class="pls-custom-order-form__field">
                        <label for="pls-message">
                            <?php esc_html_e( 'Your Message', 'pls-private-label-store' ); ?> <span class="required">*</span>
                        </label>
                        <textarea id="pls-message" name="message" rows="6" required placeholder="<?php esc_attr_e( 'Tell us about your custom product needs, special requirements, etc.', 'pls-private-label-store' ); ?>"></textarea>
                    </div>

                    <div class="pls-custom-order-form__messages" id="pls-custom-order-messages"></div>

                    <div class="pls-custom-order-form__actions">
                        <button type="submit" class="pls-custom-order-form__submit">
                            <?php esc_html_e( 'Submit Request', 'pls-private-label-store' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle form submission via AJAX.
     */
    public static function handle_submission() {
        check_ajax_referer( 'pls_custom_order_nonce', 'nonce' );

        $contact_name  = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
        $contact_email = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
        $contact_phone = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
        $company_name  = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        $category_id   = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
        $message       = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        $quantity      = isset( $_POST['quantity_needed'] ) ? absint( $_POST['quantity_needed'] ) : null;
        $budget        = isset( $_POST['budget'] ) ? floatval( $_POST['budget'] ) : null;
        $timeline      = isset( $_POST['timeline'] ) ? sanitize_text_field( wp_unslash( $_POST['timeline'] ) ) : null;

        // Validation
        if ( empty( $contact_name ) || empty( $contact_email ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'pls-private-label-store' ) ) );
        }

        if ( ! is_email( $contact_email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.', 'pls-private-label-store' ) ) );
        }

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        $result = $wpdb->insert(
            $table,
            array(
                'status'         => 'new_lead',
                'contact_name'  => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone ?: null,
                'company_name'  => $company_name ?: null,
                'category_id'   => $category_id ?: null,
                'message'       => $message,
                'quantity_needed' => $quantity,
                'budget'        => $budget,
                'timeline'      => $timeline,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%f', '%s' )
        );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to submit your request. Please try again.', 'pls-private-label-store' ) ) );
        }

        $order_id = $wpdb->insert_id;

        // Send email notification to admin
        $admin_email = get_option( 'admin_email' );
        $subject     = sprintf( __( 'New Custom Order Request #%d', 'pls-private-label-store' ), $order_id );
        $email_body  = sprintf(
            __( "New custom order request received:\n\nName: %s\nEmail: %s\nPhone: %s\nCompany: %s\nCategory: %s\nQuantity: %s\nBudget: %s\nTimeline: %s\n\nMessage:\n%s\n\nView in admin: %s", 'pls-private-label-store' ),
            $contact_name,
            $contact_email,
            $contact_phone ?: __( 'Not provided', 'pls-private-label-store' ),
            $company_name ?: __( 'Not provided', 'pls-private-label-store' ),
            $category_id ? get_term( $category_id )->name : __( 'Other', 'pls-private-label-store' ),
            $quantity ?: __( 'Not specified', 'pls-private-label-store' ),
            $budget ? '$' . number_format( $budget, 2 ) : __( 'Not specified', 'pls-private-label-store' ),
            $timeline ?: __( 'Not specified', 'pls-private-label-store' ),
            $message,
            admin_url( 'admin.php?page=pls-custom-orders' )
        );

        wp_mail( $admin_email, $subject, $email_body );

        // Get thank you page URL from settings
        $thank_you_url = get_option( 'pls_custom_order_thank_you_url', '' );
        
        // If URL provided, add order ID as query parameter
        if ( $thank_you_url ) {
            $thank_you_url = add_query_arg( 'order_id', $order_id, $thank_you_url );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Thank you! Your request has been submitted. We\'ll contact you soon.', 'pls-private-label-store' ),
                'order_id' => $order_id,
                'redirect_url' => $thank_you_url,
            )
        );
    }
}
