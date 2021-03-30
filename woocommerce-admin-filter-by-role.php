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

function get_roles() {
  global $wp_roles;
  $all_roles = $wp_roles->roles;
  return apply_filters('editable_roles', $all_roles);
}

function add_user_role_settings() {
  $editable_roles = get_roles();
  $roles = array(
    array(
      'value' => '*',
      'label' => 'All Customers',
    ),
    array(
      'value' => 'all_regular',
      'label' => 'Regular Customers',
    ),
    array(
      'value' => 'all_wholesale',
      'label' => 'All Wholesale Customers',
    ),
  );
	foreach ($editable_roles as $role => $details) {
			$sub['value'] = esc_attr($role);
			$sub['label'] = $details['name'];
			$sub['details'] = $details['capabilities'];
			$roles[] = $sub;
	}
  $data_registry = Automattic\WooCommerce\Blocks\Package::container()->get(
      Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
  );
  $data_registry->add( 'roles', $roles );
}

add_action( 'init', 'add_user_role_settings' );

function add_join_subquery( $clauses ) {
  global $wpdb;
	if ( isset( $_GET['role'] ) ) {
		$role = sanitize_text_field( wp_unslash( $_GET['role'] ) );
    if ( $role == '*' ) return $clauses;
    $clauses[] = "JOIN {$wpdb->postmeta} ON {$wpdb->prefix}wc_order_stats.order_id = {$wpdb->postmeta}.post_id";
  }
  return $clauses;
}

add_filter( 'woocommerce_analytics_clauses_join_orders_subquery', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_orders_stats_total', 'add_join_subquery' );
add_filter( 'woocommerce_analytics_clauses_join_orders_stats_interval', 'add_join_subquery' );


function apply_role_arg( $args ) {
	if ( isset( $_GET['role'] ) ) {
		$args['role'] = sanitize_text_field( wp_unslash( $_GET['role'] ) );
	}
	return $args;
}

add_filter( 'woocommerce_analytics_orders_query_args', 'apply_role_arg' );
add_filter( 'woocommerce_analytics_orders_stats_query_args', 'apply_role_arg' );

function add_where_subquery( $clauses ) {
  global $wpdb;
	if ( isset( $_GET['role'] ) ) {
		$role = sanitize_text_field( wp_unslash( $_GET['role'] ) );
    if ( $role == '*' ) return $clauses;
    $all_roles = get_roles();
    if ( $role == 'all_regular' ) {
      $matching_roles = array_filter($all_roles, function($r) {
        return !array_key_exists('have_wholesale_price', $r['capabilities']);
      });
      foreach ($matching_roles as $k => $v) {
        $search_roles[] = $k;
      }
    } else if ( $role == 'all_wholesale' ) {
      $matching_roles = array_filter($all_roles, function($r) {
        return $r['capabilities']['have_wholesale_price'] === true;
      });
      foreach ($matching_roles as $k => $v) {
        $search_roles[] = $k;
      }
    } else {
      $search_roles = array($role);
    }
		$users = get_users(array(
			'role__in' => $search_roles,
		));
    $user_ids = array();
		foreach ($users as $user) {
			$user_ids[] = $user->id;
		}
    $user_ids_str = implode($user_ids, ',');
    $clauses[] = "AND {$wpdb->postmeta}.meta_key = '_customer_user' AND {$wpdb->postmeta}.meta_value in ({$user_ids_str})";
	}
	return $clauses;
}

add_filter( 'woocommerce_analytics_clauses_where_orders_subquery', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_orders_stats_total', 'add_where_subquery' );
add_filter( 'woocommerce_analytics_clauses_where_orders_stats_interval', 'add_where_subquery' );
