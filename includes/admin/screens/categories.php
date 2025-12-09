<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$categories = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $categories ) ) {
    $categories = array();
}
?>
<div class="wrap pls-wrap">
  <h1><?php esc_html_e( 'PLS – Categories', 'pls-private-label-store' ); ?></h1>
  <p class="description"><?php esc_html_e( 'WooCommerce remains the taxonomy store for categories. Use this view to quickly review and jump into existing terms while we migrate to the standalone PLS category builder.', 'pls-private-label-store' ); ?></p>
  <table class="widefat fixed striped">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></th>
        <th><?php esc_html_e( 'Slug', 'pls-private-label-store' ); ?></th>
        <th><?php esc_html_e( 'Count', 'pls-private-label-store' ); ?></th>
        <th><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if ( empty( $categories ) ) : ?>
        <tr><td colspan="4"><?php esc_html_e( 'No categories found.', 'pls-private-label-store' ); ?></td></tr>
      <?php else : ?>
        <?php foreach ( $categories as $cat ) : ?>
          <tr>
            <td><?php echo esc_html( $cat->name ); ?></td>
            <td><code><?php echo esc_html( $cat->slug ); ?></code></td>
            <td><?php echo esc_html( intval( $cat->count ) ); ?></td>
            <td><a href="<?php echo esc_url( get_edit_term_link( $cat->term_id, 'product_cat' ) ); ?>"><?php esc_html_e( 'Edit in WooCommerce', 'pls-private-label-store' ); ?></a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
