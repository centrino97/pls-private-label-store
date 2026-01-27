<?php
/**
 * User Setup utility page for handoff verification.
 * Helps verify and create user accounts for Rober and Raniya.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only allow administrators to access this page
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'pls-private-label-store' ) );
}

// Handle user creation/role assignment
if ( isset( $_POST['pls_setup_users'] ) && check_admin_referer( 'pls_setup_users', 'pls_setup_users_nonce' ) ) {
    $users_created = array();
    $users_updated = array();
    $errors = array();

    // Get PLS User role
    $pls_role = PLS_Capabilities::ROLE_PLS_USER;

    // Ensure PLS role exists
    PLS_Capabilities::maybe_create_pls_user_role();

    // Process Rober
    if ( isset( $_POST['robert_email'] ) && ! empty( $_POST['robert_email'] ) ) {
        $robert_email = sanitize_email( $_POST['robert_email'] );
        $robert_username = isset( $_POST['robert_username'] ) ? sanitize_user( $_POST['robert_username'] ) : 'robert';
        $robert_password = isset( $_POST['robert_password'] ) ? $_POST['robert_password'] : wp_generate_password( 12, false );

        $robert = get_user_by( 'email', $robert_email );
        if ( ! $robert ) {
            $robert = get_user_by( 'login', $robert_username );
        }

        if ( ! $robert ) {
            // Create new user
            $user_id = wp_create_user( $robert_username, $robert_password, $robert_email );
            if ( is_wp_error( $user_id ) ) {
                $errors[] = 'Rober: ' . $user_id->get_error_message();
            } else {
                $users_created[] = 'Rober';
                $robert = new WP_User( $user_id );
            }
        }

        if ( $robert && ! is_wp_error( $robert ) ) {
            // Assign PLS role
            if ( ! in_array( $pls_role, $robert->roles, true ) ) {
                $robert->add_role( $pls_role );
                $users_updated[] = 'Rober';
            }
        }
    }

    // Process Raniya
    if ( isset( $_POST['raniya_email'] ) && ! empty( $_POST['raniya_email'] ) ) {
        $raniya_email = sanitize_email( $_POST['raniya_email'] );
        $raniya_username = isset( $_POST['raniya_username'] ) ? sanitize_user( $_POST['raniya_username'] ) : 'raniya';
        $raniya_password = isset( $_POST['raniya_password'] ) ? $_POST['raniya_password'] : wp_generate_password( 12, false );

        $raniya = get_user_by( 'email', $raniya_email );
        if ( ! $raniya ) {
            $raniya = get_user_by( 'login', $raniya_username );
        }

        if ( ! $raniya ) {
            // Create new user
            $user_id = wp_create_user( $raniya_username, $raniya_password, $raniya_email );
            if ( is_wp_error( $user_id ) ) {
                $errors[] = 'Raniya: ' . $user_id->get_error_message();
            } else {
                $users_created[] = 'Raniya';
                $raniya = new WP_User( $user_id );
            }
        }

        if ( $raniya && ! is_wp_error( $raniya ) ) {
            // Assign PLS role
            if ( ! in_array( $pls_role, $raniya->roles, true ) ) {
                $raniya->add_role( $pls_role );
                $users_updated[] = 'Raniya';
            }
        }
    }

    // Show success/error messages
    if ( ! empty( $users_created ) ) {
        echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Users created: %s', 'pls-private-label-store' ), implode( ', ', $users_created ) ) ) . '</p></div>';
    }
    if ( ! empty( $users_updated ) ) {
        echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Users updated: %s', 'pls-private-label-store' ), implode( ', ', $users_updated ) ) ) . '</p></div>';
    }
    if ( ! empty( $errors ) ) {
        foreach ( $errors as $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
        }
    }
}

// Check existing users
$robert = null;
$raniya = null;

$users = get_users( array( 'search' => 'robert', 'search_columns' => array( 'user_login', 'user_email', 'display_name' ) ) );
if ( ! empty( $users ) ) {
    $robert = $users[0];
}

$users = get_users( array( 'search' => 'raniya', 'search_columns' => array( 'user_login', 'user_email', 'display_name' ) ) );
if ( ! empty( $users ) ) {
    $raniya = $users[0];
}

// Check PLS role
$pls_role = PLS_Capabilities::ROLE_PLS_USER;
$role_exists = get_role( $pls_role );
?>
<div class="wrap pls-wrap">
    <h1><?php esc_html_e( 'User Setup for Handoff', 'pls-private-label-store' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Verify and create user accounts for Rober and Raniya with PLS User role.', 'pls-private-label-store' ); ?></p>

    <div class="pls-user-setup-status" style="margin: 20px 0; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
        <h2><?php esc_html_e( 'Current Status', 'pls-private-label-store' ); ?></h2>
        
        <h3><?php esc_html_e( 'PLS User Role', 'pls-private-label-store' ); ?></h3>
        <?php if ( $role_exists ) : ?>
            <p style="color: green;">✓ <?php esc_html_e( 'PLS User role exists', 'pls-private-label-store' ); ?></p>
        <?php else : ?>
            <p style="color: red;">✗ <?php esc_html_e( 'PLS User role does not exist', 'pls-private-label-store' ); ?></p>
            <p><?php esc_html_e( 'The role will be created automatically when you submit the form below.', 'pls-private-label-store' ); ?></p>
        <?php endif; ?>

        <h3><?php esc_html_e( 'User Accounts', 'pls-private-label-store' ); ?></h3>
        
        <h4>Rober</h4>
        <?php if ( $robert ) : ?>
            <p style="color: green;">✓ <?php esc_html_e( 'User found:', 'pls-private-label-store' ); ?> <?php echo esc_html( $robert->user_login ); ?> (<?php echo esc_html( $robert->user_email ); ?>)</p>
            <p><strong><?php esc_html_e( 'Roles:', 'pls-private-label-store' ); ?></strong> <?php echo esc_html( implode( ', ', $robert->roles ) ); ?></p>
            <?php if ( in_array( $pls_role, $robert->roles, true ) ) : ?>
                <p style="color: green;">✓ <?php esc_html_e( 'Has PLS User role', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <p style="color: orange;">⚠ <?php esc_html_e( 'Does NOT have PLS User role', 'pls-private-label-store' ); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <p style="color: red;">✗ <?php esc_html_e( 'User not found', 'pls-private-label-store' ); ?></p>
        <?php endif; ?>

        <h4>Raniya</h4>
        <?php if ( $raniya ) : ?>
            <p style="color: green;">✓ <?php esc_html_e( 'User found:', 'pls-private-label-store' ); ?> <?php echo esc_html( $raniya->user_login ); ?> (<?php echo esc_html( $raniya->user_email ); ?>)</p>
            <p><strong><?php esc_html_e( 'Roles:', 'pls-private-label-store' ); ?></strong> <?php echo esc_html( implode( ', ', $raniya->roles ) ); ?></p>
            <?php if ( in_array( $pls_role, $raniya->roles, true ) ) : ?>
                <p style="color: green;">✓ <?php esc_html_e( 'Has PLS User role', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <p style="color: orange;">⚠ <?php esc_html_e( 'Does NOT have PLS User role', 'pls-private-label-store' ); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <p style="color: red;">✗ <?php esc_html_e( 'User not found', 'pls-private-label-store' ); ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="" style="margin-top: 30px;">
        <?php wp_nonce_field( 'pls_setup_users', 'pls_setup_users_nonce' ); ?>
        
        <h2><?php esc_html_e( 'Create or Update Users', 'pls-private-label-store' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Fill in the information below to create or update user accounts. Leave password blank to auto-generate.', 'pls-private-label-store' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="robert_email"><?php esc_html_e( 'Rober Email', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="email" id="robert_email" name="robert_email" value="<?php echo $robert ? esc_attr( $robert->user_email ) : ''; ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Email address for Rober', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="robert_username"><?php esc_html_e( 'Rober Username', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="text" id="robert_username" name="robert_username" value="<?php echo $robert ? esc_attr( $robert->user_login ) : 'robert'; ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Username (only used if creating new user)', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="robert_password"><?php esc_html_e( 'Rober Password', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="text" id="robert_password" name="robert_password" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Leave blank to auto-generate', 'pls-private-label-store' ); ?>" />
                    <p class="description"><?php esc_html_e( 'Password (leave blank to auto-generate secure password)', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="raniya_email"><?php esc_html_e( 'Raniya Email', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="email" id="raniya_email" name="raniya_email" value="<?php echo $raniya ? esc_attr( $raniya->user_email ) : ''; ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Email address for Raniya', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="raniya_username"><?php esc_html_e( 'Raniya Username', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="text" id="raniya_username" name="raniya_username" value="<?php echo $raniya ? esc_attr( $raniya->user_login ) : 'raniya'; ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Username (only used if creating new user)', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="raniya_password"><?php esc_html_e( 'Raniya Password', 'pls-private-label-store' ); ?></label></th>
                <td>
                    <input type="text" id="raniya_password" name="raniya_password" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Leave blank to auto-generate', 'pls-private-label-store' ); ?>" />
                    <p class="description"><?php esc_html_e( 'Password (leave blank to auto-generate secure password)', 'pls-private-label-store' ); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="pls_setup_users" class="button button-primary" value="<?php esc_attr_e( 'Create/Update Users', 'pls-private-label-store' ); ?>" />
        </p>
    </form>

    <div class="pls-user-setup-notes" style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
        <h3><?php esc_html_e( 'Important Notes', 'pls-private-label-store' ); ?></h3>
        <ul>
            <li><?php esc_html_e( 'Users with PLS User role can only access PLS pages, not full WordPress admin.', 'pls-private-label-store' ); ?></li>
            <li><?php esc_html_e( 'If users already exist, they will be updated with PLS User role.', 'pls-private-label-store' ); ?></li>
            <li><?php esc_html_e( 'If auto-generating passwords, make sure to share them securely with users.', 'pls-private-label-store' ); ?></li>
            <li><?php esc_html_e( 'Users can reset their passwords via WordPress login page.', 'pls-private-label-store' ); ?></li>
        </ul>
    </div>
</div>
