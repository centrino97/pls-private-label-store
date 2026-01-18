<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle sample data generation
if ( isset( $_POST['pls_generate_sample_data'] ) && check_admin_referer( 'pls_generate_sample_data', 'pls_sample_data_nonce' ) ) {
    if ( current_user_can( 'manage_options' ) ) {
        require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
        PLS_Sample_Data::generate();
        $message = 'sample-data-generated';
        wp_safe_redirect( add_query_arg( 'message', $message, admin_url( 'admin.php?page=pls-settings' ) ) );
        exit;
    }
}

// Handle form submission
if ( isset( $_POST['pls_save_settings'] ) && check_admin_referer( 'pls_save_settings', 'pls_settings_nonce' ) ) {
    $commission_rates = array(
        'tiers' => array(
            'tier_1' => isset( $_POST['tier_1'] ) ? floatval( $_POST['tier_1'] ) : 0.80,
            'tier_2' => isset( $_POST['tier_2'] ) ? floatval( $_POST['tier_2'] ) : 0.75,
            'tier_3' => isset( $_POST['tier_3'] ) ? floatval( $_POST['tier_3'] ) : 0.65,
            'tier_4' => isset( $_POST['tier_4'] ) ? floatval( $_POST['tier_4'] ) : 0.40,
            'tier_5' => isset( $_POST['tier_5'] ) ? floatval( $_POST['tier_5'] ) : 0.29,
        ),
        'bundles' => array(
            'mini_line'    => isset( $_POST['bundle_mini_line'] ) ? floatval( $_POST['bundle_mini_line'] ) : 0.59,
            'starter_line' => isset( $_POST['bundle_starter_line'] ) ? floatval( $_POST['bundle_starter_line'] ) : 0.49,
            'growth_line'  => isset( $_POST['bundle_growth_line'] ) ? floatval( $_POST['bundle_growth_line'] ) : 0.32,
            'premium_line' => isset( $_POST['bundle_premium_line'] ) ? floatval( $_POST['bundle_premium_line'] ) : 0.25,
        ),
        'custom_order_percent' => isset( $_POST['custom_order_percent'] ) ? floatval( $_POST['custom_order_percent'] ) : 3.00,
    );

    update_option( 'pls_commission_rates', $commission_rates );

    // Save label pricing
    $label_price = isset( $_POST['label_price_tier_1_2'] ) ? round( floatval( $_POST['label_price_tier_1_2'] ), 2 ) : 0.50;
    if ( $label_price < 0 ) {
        $label_price = 0;
    }
    update_option( 'pls_label_price_tier_1_2', $label_price );

    // Save commission email recipients
    $email_recipients = isset( $_POST['commission_email_recipients'] ) ? sanitize_text_field( wp_unslash( $_POST['commission_email_recipients'] ) ) : '';
    if ( $email_recipients ) {
        $emails = array_map( 'trim', explode( ',', $email_recipients ) );
        $emails = array_filter( array_map( 'sanitize_email', $emails ) );
        update_option( 'pls_commission_email_recipients', $emails );
    }

    // Handle onboarding reset
    if ( isset( $_POST['reset_onboarding'] ) ) {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';
        $wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );
        $message = 'onboarding-reset';
    } elseif ( isset( $_POST['reset_onboarding_all'] ) && current_user_can( 'manage_options' ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';
        $wpdb->query( "TRUNCATE TABLE {$table}" );
        $message = 'onboarding-reset-all';
    } else {
        $message = 'settings-saved';
    }

    wp_safe_redirect( add_query_arg( 'message', $message, admin_url( 'admin.php?page=pls-settings' ) ) );
    exit;
}

$commission_rates = get_option( 'pls_commission_rates', array() );
$tier_rates       = isset( $commission_rates['tiers'] ) ? $commission_rates['tiers'] : array();
$bundle_rates     = isset( $commission_rates['bundles'] ) ? $commission_rates['bundles'] : array();
$custom_order_percent = isset( $commission_rates['custom_order_percent'] ) ? $commission_rates['custom_order_percent'] : 3.00;
$label_price      = get_option( 'pls_label_price_tier_1_2', '0.50' );

if ( isset( $_GET['message'] ) && 'settings-saved' === $_GET['message'] ) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'pls-private-label-store' ) . '</p></div>';
}
?>
<div class="wrap pls-wrap pls-page-settings">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Settings', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'PLS Settings', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Configure commission rates and pricing settings.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'pls_save_settings', 'pls_settings_nonce' ); ?>
        <input type="hidden" name="pls_save_settings" value="1" />

        <!-- Commission Rates Section -->
        <div class="pls-settings-section">
            <h2><?php esc_html_e( 'Commission Rates', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set commission rates per unit for each pack tier and bundle type.', 'pls-private-label-store' ); ?></p>

            <h3><?php esc_html_e( 'Pack Tier Rates (per unit)', 'pls-private-label-store' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="tier_1"><?php esc_html_e( 'Trial Pack (50 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="tier_1" name="tier_1" 
                               value="<?php echo esc_attr( isset( $tier_rates['tier_1'] ) ? $tier_rates['tier_1'] : '0.80' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="tier_2"><?php esc_html_e( 'Starter Pack (100 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="tier_2" name="tier_2" 
                               value="<?php echo esc_attr( isset( $tier_rates['tier_2'] ) ? $tier_rates['tier_2'] : '0.75' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="tier_3"><?php esc_html_e( 'Brand Entry (250 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="tier_3" name="tier_3" 
                               value="<?php echo esc_attr( isset( $tier_rates['tier_3'] ) ? $tier_rates['tier_3'] : '0.65' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="tier_4"><?php esc_html_e( 'Growth Brand (500 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="tier_4" name="tier_4" 
                               value="<?php echo esc_attr( isset( $tier_rates['tier_4'] ) ? $tier_rates['tier_4'] : '0.40' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="tier_5"><?php esc_html_e( 'Wholesale Launch (1000 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="tier_5" name="tier_5" 
                               value="<?php echo esc_attr( isset( $tier_rates['tier_5'] ) ? $tier_rates['tier_5'] : '0.29' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Bundle Rates (per unit)', 'pls-private-label-store' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="bundle_mini_line"><?php esc_html_e( 'Mini Line (500 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="bundle_mini_line" name="bundle_mini_line" 
                               value="<?php echo esc_attr( isset( $bundle_rates['mini_line'] ) ? $bundle_rates['mini_line'] : '0.59' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="bundle_starter_line"><?php esc_html_e( 'Starter Line (900 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="bundle_starter_line" name="bundle_starter_line" 
                               value="<?php echo esc_attr( isset( $bundle_rates['starter_line'] ) ? $bundle_rates['starter_line'] : '0.49' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="bundle_growth_line"><?php esc_html_e( 'Growth Line (1600 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="bundle_growth_line" name="bundle_growth_line" 
                               value="<?php echo esc_attr( isset( $bundle_rates['growth_line'] ) ? $bundle_rates['growth_line'] : '0.32' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="bundle_premium_line"><?php esc_html_e( 'Premium Line (3000 units)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="bundle_premium_line" name="bundle_premium_line" 
                               value="<?php echo esc_attr( isset( $bundle_rates['premium_line'] ) ? $bundle_rates['premium_line'] : '0.25' ); ?>" 
                               class="small-text" />
                        <span class="description"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Custom Order Commission', 'pls-private-label-store' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="custom_order_percent"><?php esc_html_e( 'Commission Percentage', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <input type="number" step="0.01" id="custom_order_percent" name="custom_order_percent" 
                               value="<?php echo esc_attr( $custom_order_percent ); ?>" 
                               class="small-text" min="0" max="100" />
                        <span>%</span>
                        <p class="description"><?php esc_html_e( 'Percentage of total order value for custom orders.', 'pls-private-label-store' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Label Pricing Section -->
        <div class="pls-settings-section">
            <h2><?php esc_html_e( 'Label Application Pricing', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set automatic pricing for label application based on tier. Tier 3-5 are automatically FREE.', 'pls-private-label-store' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><label for="label_price_tier_1_2"><?php esc_html_e( 'Tier 1-2:', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <span>$</span>
                        <input type="number" step="0.01" id="label_price_tier_1_2" name="label_price_tier_1_2" 
                               value="<?php echo esc_attr( $label_price ); ?>" 
                               class="small-text" min="0" />
                        <span><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                        <p class="description"><?php esc_html_e( 'This price will be multiplied by the number of units in the pack tier.', 'pls-private-label-store' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Tier 3-5:', 'pls-private-label-store' ); ?></th>
                    <td>
                        <strong style="color: #00a32a;"><?php esc_html_e( 'FREE', 'pls-private-label-store' ); ?></strong>
                        <span style="margin-left: 8px; color: #646970; font-size: 13px;">
                            <?php esc_html_e( '(automatically applied)', 'pls-private-label-store' ); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Commission Email Settings -->
        <div class="pls-settings-section">
            <h2><?php esc_html_e( 'Commission Email Settings', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure email notifications for commission reports.', 'pls-private-label-store' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><label for="commission_email_recipients"><?php esc_html_e( 'Recipient Email(s)', 'pls-private-label-store' ); ?></label></th>
                    <td>
                        <input type="text" id="commission_email_recipients" name="commission_email_recipients" 
                               value="<?php echo esc_attr( $email_recipients_string ); ?>" 
                               class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Comma-separated list of email addresses to receive monthly commission reports.', 'pls-private-label-store' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Sample Data -->
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
            <div class="pls-settings-section">
                <h2><?php esc_html_e( 'Sample Data', 'pls-private-label-store' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Generate sample products, categories, ingredients, and product options for testing. This will clean up existing data and add sample data.', 'pls-private-label-store' ); ?></p>
                
                <form method="post" action="" style="margin-top: 16px;">
                    <?php wp_nonce_field( 'pls_generate_sample_data', 'pls_sample_data_nonce' ); ?>
                    <button type="submit" name="pls_generate_sample_data" value="1" class="button button-secondary" 
                            onclick="return confirm('<?php esc_attr_e( 'This will DELETE all existing products, custom orders, commissions, and attributes (except Pack Tiers). Are you sure?', 'pls-private-label-store' ); ?>');">
                        <?php esc_html_e( 'Generate Sample Data', 'pls-private-label-store' ); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Onboarding Settings -->
        <div class="pls-settings-section">
            <h2><?php esc_html_e( 'Onboarding', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Reset onboarding progress to restart the tutorial.', 'pls-private-label-store' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Reset Onboarding', 'pls-private-label-store' ); ?></th>
                    <td>
                        <button type="submit" name="reset_onboarding" value="1" class="button" 
                                onclick="return confirm('<?php esc_attr_e( 'Reset onboarding for your account?', 'pls-private-label-store' ); ?>');">
                            <?php esc_html_e( 'Reset for Current User', 'pls-private-label-store' ); ?>
                        </button>
                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                            <button type="submit" name="reset_onboarding_all" value="1" class="button" 
                                    onclick="return confirm('<?php esc_attr_e( 'Reset onboarding for ALL users? This cannot be undone.', 'pls-private-label-store' ); ?>');">
                                <?php esc_html_e( 'Reset for All Users', 'pls-private-label-store' ); ?>
                            </button>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'This will allow you to restart the onboarding tutorial.', 'pls-private-label-store' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary" name="pls_save_settings" value="1"><?php esc_html_e( 'Save Settings', 'pls-private-label-store' ); ?></button>
        </p>
    </form>
</div>
