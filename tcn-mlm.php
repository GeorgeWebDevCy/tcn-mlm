<?php
/**
 * Plugin Name:       TCN MLM
 * Plugin URI:        https://github.com/GeorgeWebDevCy/tcn-mlm
 * Description:       Network marketing automation for WooCommerce memberships.
 * Version:           0.1.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            TCN
 * Author URI:        https://georgewebdevcy.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tcn-mlm
 * Domain Path:       /languages
 *
 * @package TCN\MLM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TCN_MLM_VERSION' ) ) {
	define( 'TCN_MLM_VERSION', '0.1.5' );
}

if ( ! defined( 'TCN_MLM_PLUGIN_FILE' ) ) {
	define( 'TCN_MLM_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'TCN_MLM_PLUGIN_DIR' ) ) {
	define( 'TCN_MLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TCN_MLM_PLUGIN_URL' ) ) {
	define( 'TCN_MLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( file_exists( TCN_MLM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once TCN_MLM_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once TCN_MLM_PLUGIN_DIR . 'includes/Autoloader.php';

( new \TCN\MLM\Autoloader( TCN_MLM_PLUGIN_DIR . 'includes' ) )->register();

register_activation_hook( __FILE__, 'tcn_mlm_activate' );
register_deactivation_hook( __FILE__, 'tcn_mlm_deactivate' );

add_action( 'plugins_loaded', 'tcn_mlm_bootstrap', 5 );

/**
 * Handle plugin activation requirements.
 */
function tcn_mlm_activate(): void {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		tcn_mlm_deactivate_with_message( __( 'TCN MLM requires PHP 7.4 or newer.', 'tcn-mlm' ) );
	}

	global $wp_version;

	if ( version_compare( $wp_version, '6.0', '<' ) ) {
		tcn_mlm_deactivate_with_message( __( 'TCN MLM requires WordPress 6.0 or newer.', 'tcn-mlm' ) );
	}

	tcn_mlm_seed_default_options();
	tcn_mlm_register_account_endpoints();
	tcn_mlm_seed_membership_products();
	flush_rewrite_rules();
}

/**
 * Remove scheduled events and flush rewrite rules on deactivation.
 */
function tcn_mlm_deactivate(): void {
	wp_clear_scheduled_hook( 'tcn_mlm_sync_memberships' );
	flush_rewrite_rules();
}

/**
 * Safely deactivate the plugin and show an admin notice.
 */
function tcn_mlm_deactivate_with_message( string $message ): void {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( esc_html( $message ) );
}

/**
 * Boot the plugin once all dependencies are loaded.
 */
function tcn_mlm_bootstrap(): void {
	load_plugin_textdomain( 'tcn-mlm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	tcn_mlm_bootstrap_update_checker();

	\TCN\MLM\Plugin::instance()->boot();
}

function tcn_mlm_register_account_endpoints(): void {
	foreach ( array_keys( \TCN\MLM\WooCommerce\AccountEndpoints::ENDPOINTS ) as $slug ) {
		add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );
	}
}

function tcn_mlm_seed_default_options(): void {
	if ( false === get_option( 'tcn_mlm_levels', false ) ) {
		update_option(
			'tcn_mlm_levels',
			[
				'default' => 'blue',
				'levels'  => [
					'blue'     => [ 'label' => __( 'Blue', 'tcn-mlm' ), 'fee' => 0 ],
					'gold'     => [ 'label' => __( 'Gold', 'tcn-mlm' ), 'fee' => 199 ],
					'platinum' => [ 'label' => __( 'Platinum', 'tcn-mlm' ), 'fee' => 399 ],
					'black'    => [ 'label' => __( 'Black', 'tcn-mlm' ), 'fee' => 699 ],
				],
			],
			false
		);
	}

	if ( false === get_option( 'tcn_mlm_general', false ) ) {
		update_option(
			'tcn_mlm_general',
			[
				'default_sponsor_id' => '',
			],
			false
		);
	}
}

function tcn_mlm_seed_membership_products(): void {
	if ( ! class_exists( '\\WC_Product' ) ) {
		return;
	}

	$levels_option = get_option( 'tcn_mlm_levels', [] );
	$levels        = [];

	if ( isset( $levels_option['levels'] ) && is_array( $levels_option['levels'] ) ) {
		foreach ( $levels_option['levels'] as $key => $level ) {
			$levels[ sanitize_key( $key ) ] = is_array( $level ) && isset( $level['label'] )
				? $level['label']
				: ucfirst( $key );
		}
	} else {
		$levels = [
			'blue'     => __( 'Blue Membership', 'tcn-mlm' ),
			'gold'     => __( 'Gold Membership', 'tcn-mlm' ),
			'platinum' => __( 'Platinum Membership', 'tcn-mlm' ),
			'black'    => __( 'Black Membership', 'tcn-mlm' ),
		];
	}

	foreach ( $levels as $slug => $label ) {
		tcn_mlm_ensure_membership_product( sanitize_key( $slug ), $label );
	}
}

function tcn_mlm_ensure_membership_product( string $level, string $label ): void {
	$product = tcn_mlm_find_product_by_level( $level );

	if ( ! $product ) {
		$product = tcn_mlm_find_product_by_title( $label );
	}

	if ( ! $product ) {
		$product = new WC_Product();
		$product->set_name( wp_strip_all_tags( $label ) );
		$product->set_slug( sanitize_title( 'tcn-mlm-' . $level ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_price( 0 );
		$product->set_regular_price( 0 );
		$product->set_manage_stock( false );
		$product->set_sold_individually( true );
		$product->set_virtual( true );
		$product->save();
	}

	$product->update_meta_data( '_tcn_membership_level', $level );
	$product->save();
}

function tcn_mlm_find_product_by_level( string $level ) {
	$args = [
		'post_type'   => 'product',
		'post_status' => 'publish',
		'meta_query'  => [
			[
				'key'   => '_tcn_membership_level',
				'value' => $level,
			],
		],
		'posts_per_page' => 1,
		'fields'         => 'ids',
	];

	$query = new WP_Query( $args );

	if ( empty( $query->posts ) ) {
		wp_reset_postdata();
		return null;
	}

	$product_id = $query->posts[0];

	wp_reset_postdata();

	return wc_get_product( $product_id );
}

function tcn_mlm_find_product_by_title( string $label ) {
	$page = get_page_by_title( wp_strip_all_tags( $label ), OBJECT, 'product' );

	if ( ! $page ) {
		return null;
	}

	return wc_get_product( $page->ID );
}

/**
 * Wire the plugin-update-checker library against this repository.
 */
function tcn_mlm_bootstrap_update_checker(): void {
	static $initialized = false;
	static $update_checker = null;

	if ( $initialized || $update_checker ) {
		return;
	}

	$library_path = TCN_MLM_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

	if ( file_exists( TCN_MLM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once TCN_MLM_PLUGIN_DIR . 'vendor/autoload.php';
	}

	if ( file_exists( $library_path ) ) {
		require_once $library_path;
	}

	if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		add_action( 'admin_notices', 'tcn_mlm_update_checker_missing_notice' );
		return;
	}

	$default_repository = defined( 'TCN_MLM_UPDATE_REPOSITORY' )
		? TCN_MLM_UPDATE_REPOSITORY
		: 'https://github.com/GeorgeWebDevCy/tcn-mlm';
	$default_branch     = defined( 'TCN_MLM_UPDATE_REPOSITORY_BRANCH' )
		? TCN_MLM_UPDATE_REPOSITORY_BRANCH
		: 'main';

	$repository = apply_filters( 'tcn_mlm_update_repository_url', $default_repository );
	$branch     = apply_filters( 'tcn_mlm_update_repository_branch', $default_branch );

	if ( empty( $repository ) ) {
		return;
	}

	$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		$repository,
		TCN_MLM_PLUGIN_FILE,
		'tcn-mlm'
	);

	if ( $branch ) {
		$update_checker->setBranch( $branch );
	}

	$initialized = true;
}

/**
 * Display a dismissible admin notice when the update checker library is missing.
 */
function tcn_mlm_update_checker_missing_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-warning is-dismissible"><p>';
	echo wp_kses_post(
		__(
			'TCN MLM could not load the plugin-update-checker library. Ensure the plugin ships with the plugin-update-checker/ directory or provide an autoloader that registers YahnisElsts\\PluginUpdateChecker classes.',
			'tcn-mlm'
		)
	);
	echo '</p></div>';
}
