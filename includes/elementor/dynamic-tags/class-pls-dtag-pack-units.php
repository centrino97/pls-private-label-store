<?php
/**
 * Elementor Dynamic Tag: Pack Units (stub).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Core\DynamicTags\Tag;

final class PLS_DTag_Pack_Units extends Tag {

    public function get_name() {
        return 'pls_pack_units';
    }

    public function get_title() {
        return __( 'PLS Pack Units', 'pls-private-label-store' );
    }

    public function get_group() {
        return 'pls';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        // TODO: Wire to selected variation/meta.
        echo esc_html__( '—', 'pls-private-label-store' );
    }
}
