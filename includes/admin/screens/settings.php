<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sample data generation removed in v1.2.0 - data is already configured

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
        'custom_order' => array(
            'threshold' => isset( $_POST['custom_order_threshold'] ) ? floatval( $_POST['custom_order_threshold'] ) : 100000.00,
            'rate_below' => isset( $_POST['custom_order_rate_below'] ) ? floatval( $_POST['custom_order_rate_below'] ) : 3.00,
            'rate_above' => isset( $_POST['custom_order_rate_above'] ) ? floatval( $_POST['custom_order_rate_above'] ) : 5.00,
        ),
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

// Handle migration from old single percentage to new tiered structure
$custom_order_config = isset( $commission_rates['custom_order'] ) ? $commission_rates['custom_order'] : array();
if ( empty( $custom_order_config ) && isset( $commission_rates['custom_order_percent'] ) ) {
    // Migrate old single rate to new structure
    $old_rate = floatval( $commission_rates['custom_order_percent'] );
    $custom_order_config = array(
        'threshold' => 100000.00,
        'rate_below' => $old_rate,
        'rate_above' => $old_rate,
    );
    // Update option
    $commission_rates['custom_order'] = $custom_order_config;
    unset( $commission_rates['custom_order_percent'] );
    update_option( 'pls_commission_rates', $commission_rates );
}

$custom_order_threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
$custom_order_rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
$custom_order_rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;
$label_price      = get_option( 'pls_label_price_tier_1_2', '0.50' );
$email_recipients = get_option( 'pls_commission_email_recipients', array() );
$email_recipients_string = is_array( $email_recipients ) ? implode( ', ', $email_recipients ) : '';

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

    <form method="post" action="" id="pls-settings-form">
        <?php wp_nonce_field( 'pls_save_settings', 'pls_settings_nonce' ); ?>
        <input type="hidden" name="pls_save_settings" value="1" />

        <div class="pls-accordion">
            <!-- Commission Rates Section -->
            <div class="pls-accordion__item">
                <button type="button" class="pls-accordion__header">
                    <?php esc_html_e( 'Commission Rates', 'pls-private-label-store' ); ?>
                </button>
                <div class="pls-accordion__content">
                    <p class="description" style="margin-top: 0;"><?php esc_html_e( 'Set commission rates per unit for each pack tier and bundle type.', 'pls-private-label-store' ); ?></p>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Pack Tier Rates (per unit)', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-field-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="pls-input-group">
                                <label for="tier_1"><?php esc_html_e( 'Trial Pack (50 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="tier_1" name="tier_1" 
                                           value="<?php echo esc_attr( isset( $tier_rates['tier_1'] ) ? $tier_rates['tier_1'] : '0.80' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="tier_2"><?php esc_html_e( 'Starter Pack (100 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="tier_2" name="tier_2" 
                                           value="<?php echo esc_attr( isset( $tier_rates['tier_2'] ) ? $tier_rates['tier_2'] : '0.75' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="tier_3"><?php esc_html_e( 'Brand Entry (250 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="tier_3" name="tier_3" 
                                           value="<?php echo esc_attr( isset( $tier_rates['tier_3'] ) ? $tier_rates['tier_3'] : '0.65' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="tier_4"><?php esc_html_e( 'Growth Brand (500 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="tier_4" name="tier_4" 
                                           value="<?php echo esc_attr( isset( $tier_rates['tier_4'] ) ? $tier_rates['tier_4'] : '0.40' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="tier_5"><?php esc_html_e( 'Wholesale Launch (1000 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="tier_5" name="tier_5" 
                                           value="<?php echo esc_attr( isset( $tier_rates['tier_5'] ) ? $tier_rates['tier_5'] : '0.29' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Bundle Rates (per unit)', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-field-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="pls-input-group">
                                <label for="bundle_mini_line"><?php esc_html_e( 'Mini Line (500 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="bundle_mini_line" name="bundle_mini_line" 
                                           value="<?php echo esc_attr( isset( $bundle_rates['mini_line'] ) ? $bundle_rates['mini_line'] : '0.59' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="bundle_starter_line"><?php esc_html_e( 'Starter Line (900 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="bundle_starter_line" name="bundle_starter_line" 
                                           value="<?php echo esc_attr( isset( $bundle_rates['starter_line'] ) ? $bundle_rates['starter_line'] : '0.49' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="bundle_growth_line"><?php esc_html_e( 'Growth Line (1600 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="bundle_growth_line" name="bundle_growth_line" 
                                           value="<?php echo esc_attr( isset( $bundle_rates['growth_line'] ) ? $bundle_rates['growth_line'] : '0.32' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                            <div class="pls-input-group">
                                <label for="bundle_premium_line"><?php esc_html_e( 'Premium Line (3000 units)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="bundle_premium_line" name="bundle_premium_line" 
                                           value="<?php echo esc_attr( isset( $bundle_rates['premium_line'] ) ? $bundle_rates['premium_line'] : '0.25' ); ?>" 
                                           class="pls-input" style="width: 100px;" />
                                    <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Custom Order Commission', 'pls-private-label-store' ); ?></h3>
                        <p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Tiered commission rates based on final order value. Commission is calculated when custom order status is set to "Done".', 'pls-private-label-store' ); ?></p>
                        <div class="pls-field-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                            <div class="pls-input-group">
                                <label for="custom_order_threshold"><?php esc_html_e( 'Threshold Amount (AUD)', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: var(--pls-gray-600);">$</span>
                                    <input type="number" step="0.01" id="custom_order_threshold" name="custom_order_threshold" 
                                           value="<?php echo esc_attr( $custom_order_threshold ); ?>" 
                                           class="pls-input" style="width: 150px;" min="0" />
                                </div>
                                <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Orders at or above this amount use the higher rate.', 'pls-private-label-store' ); ?></p>
                            </div>
                            <div class="pls-input-group">
                                <label for="custom_order_rate_below"><?php esc_html_e( 'Rate Below Threshold', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" step="0.01" id="custom_order_rate_below" name="custom_order_rate_below" 
                                           value="<?php echo esc_attr( $custom_order_rate_below ); ?>" 
                                           class="pls-input" style="width: 100px;" min="0" max="100" />
                                    <span style="color: var(--pls-gray-600);">%</span>
                                </div>
                                <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Commission rate for orders under threshold.', 'pls-private-label-store' ); ?></p>
                            </div>
                            <div class="pls-input-group">
                                <label for="custom_order_rate_above"><?php esc_html_e( 'Rate At/Above Threshold', 'pls-private-label-store' ); ?></label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" step="0.01" id="custom_order_rate_above" name="custom_order_rate_above" 
                                           value="<?php echo esc_attr( $custom_order_rate_above ); ?>" 
                                           class="pls-input" style="width: 100px;" min="0" max="100" />
                                    <span style="color: var(--pls-gray-600);">%</span>
                                </div>
                                <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Commission rate for orders at or above threshold.', 'pls-private-label-store' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Label Pricing Section -->
            <div class="pls-accordion__item is-collapsed">
                <button type="button" class="pls-accordion__header">
                    <?php esc_html_e( 'Label Application Pricing', 'pls-private-label-store' ); ?>
                </button>
                <div class="pls-accordion__content">
                    <p class="description" style="margin-top: 0;"><?php esc_html_e( 'Set automatic pricing for label application based on tier. Tier 3-5 are automatically FREE.', 'pls-private-label-store' ); ?></p>
                    
                    <div class="pls-input-group" style="max-width: 300px; margin-bottom: 16px;">
                        <label for="label_price_tier_1_2"><?php esc_html_e( 'Tier 1-2:', 'pls-private-label-store' ); ?></label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: var(--pls-gray-600);">$</span>
                            <input type="number" step="0.01" id="label_price_tier_1_2" name="label_price_tier_1_2" 
                                   value="<?php echo esc_attr( $label_price ); ?>" 
                                   class="pls-input" style="width: 100px;" min="0" />
                            <span class="description" style="font-size: 12px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                        </div>
                        <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'This price will be multiplied by the number of units in the pack tier.', 'pls-private-label-store' ); ?></p>
                    </div>
                    
                    <div style="padding: 12px; background: var(--pls-success-light); border-radius: 8px; display: inline-block;">
                        <strong style="color: var(--pls-success);"><?php esc_html_e( 'Tier 3-5: FREE', 'pls-private-label-store' ); ?></strong>
                        <span style="margin-left: 8px; color: var(--pls-gray-500); font-size: 13px;">
                            <?php esc_html_e( '(automatically applied)', 'pls-private-label-store' ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Commission Email Settings -->
            <div class="pls-accordion__item is-collapsed">
                <button type="button" class="pls-accordion__header">
                    <?php esc_html_e( 'Commission Email Settings', 'pls-private-label-store' ); ?>
                </button>
                <div class="pls-accordion__content">
                    <p class="description" style="margin-top: 0;"><?php esc_html_e( 'Configure email notifications for commission reports.', 'pls-private-label-store' ); ?></p>
                    
                    <div class="pls-input-group" style="max-width: 500px;">
                        <label for="commission_email_recipients"><?php esc_html_e( 'Recipient Email(s)', 'pls-private-label-store' ); ?></label>
                        <input type="text" id="commission_email_recipients" name="commission_email_recipients" 
                               value="<?php echo esc_attr( $email_recipients_string ); ?>" 
                               class="pls-input" placeholder="email1@example.com, email2@example.com" />
                        <p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Comma-separated list of email addresses to receive monthly commission reports.', 'pls-private-label-store' ); ?></p>
                    </div>
                </div>
            </div>


            <!-- Onboarding Settings -->
            <div class="pls-accordion__item is-collapsed">
                <button type="button" class="pls-accordion__header">
                    <?php esc_html_e( 'Onboarding', 'pls-private-label-store' ); ?>
                </button>
                <div class="pls-accordion__content">
                    <p class="description" style="margin-top: 0;"><?php esc_html_e( 'Reset onboarding progress to restart the tutorial.', 'pls-private-label-store' ); ?></p>
                    
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px;">
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
                    </div>
                </div>
            </div>
        </div>

        <div style="position: sticky; bottom: 0; background: #fff; padding: 16px 0; margin-top: 24px; border-top: 1px solid var(--pls-gray-200);">
            <button type="submit" class="button button-primary pls-btn--primary" name="pls_save_settings" value="1"><?php esc_html_e( 'Save Settings', 'pls-private-label-store' ); ?></button>
        </div>
    </form>
</div>
