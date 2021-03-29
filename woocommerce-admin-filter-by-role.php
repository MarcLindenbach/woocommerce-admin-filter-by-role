<?php
/**
 * Plugin Name: woocommerce-admin-filter-by-role
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function add_extension_register_script() {
	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ) {
		return;
	}

	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require( $script_asset_path )
		: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
	$script_url = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'woocommerce-admin-filter-by-role',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'woocommerce-admin-filter-by-role',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(),
		filemtime( dirname( __FILE__ ) . '/build/index.css' )
	);

	wp_enqueue_script( 'woocommerce-admin-filter-by-role' );
	wp_enqueue_style( 'woocommerce-admin-filter-by-role' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );

function add_user_role_settings() {
  global $wp_roles;
  $all_roles = $wp_roles->roles;
  $editable_roles = apply_filters('editable_roles', $all_roles);
	foreach ($editable_roles as $role => $details) {
			$sub['value'] = esc_attr($role);
			$sub['label'] = $details['name'];
			$roles[] = $sub;
	}
  $data_registry = Automattic\WooCommerce\Blocks\Package::container()->get(
      Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
  );
  $data_registry->add( 'roles', $roles );
}

add_action( 'init', 'add_user_role_settings' );

