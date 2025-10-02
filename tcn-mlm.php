<?php
/**
 * Plugin Name:       TCN MLM
 * Plugin URI:        https://github.com/GeorgeWebDevCy/tcn-mlm
 * Description:       Network marketing automation for WooCommerce memberships.
 * Version:           0.1.8
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
	define( 'TCN_MLM_VERSION', '0.1.8' );
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

function tcn_mlm_default_levels(): array {
	return [
		'blue'     => [
			'label'       => __( 'Blue Membership', 'tcn-mlm' ),
			'fee'         => 0,
			'currency'    => 'THB',
			'interval'    => 'year',
			'description' => __( 'Free access tier for new members exploring the network.', 'tcn-mlm' ),
			'features'    => [
				__( 'Discover participating vendors and news inside the app.', 'tcn-mlm' ),
				__( 'Receive general network announcements.', 'tcn-mlm' ),
			],
			'benefits'    => [
				[
					'id'                  => 'sapphire-discounts',
					'title'               => __( 'Sapphire vendor directory', 'tcn-mlm' ),
					'description'         => __( 'Browse local Sapphire vendors and discover introductory offers.', 'tcn-mlm' ),
					'discountPercentage'  => 0,
				],
			],
		],
		'gold'     => [
			'label'       => __( 'Gold Membership', 'tcn-mlm' ),
			'fee'         => 500,
			'currency'    => 'THB',
			'interval'    => 'year',
			'description' => __( 'Entry-level membership with annual perks and standard discounts.', 'tcn-mlm' ),
			'features'    => [
				__( 'Unlock network-wide member discounts.', 'tcn-mlm' ),
				__( 'Eligible for Membership Network Program progression.', 'tcn-mlm' ),
			],
			'benefits'    => [
				[
					'id'                 => 'gold-sapphire-discount',
					'title'              => __( 'Sapphire vendor savings', 'tcn-mlm' ),
					'description'        => __( 'Enjoy 2.5% savings with Sapphire partners.', 'tcn-mlm' ),
					'discountPercentage' => 2.5,
				],
				[
					'id'                 => 'gold-diamond-discount',
					'title'              => __( 'Diamond vendor savings', 'tcn-mlm' ),
					'description'        => __( 'Receive 5% off with Diamond partners.', 'tcn-mlm' ),
					'discountPercentage' => 5,
				],
			],
			'highlight'   => true,
		],
		'platinum' => [
			'label'       => __( 'Platinum Membership', 'tcn-mlm' ),
			'fee'         => 1200,
			'currency'    => 'THB',
			'interval'    => 'year',
			'description' => __( 'Enhanced benefits, higher-tier discounts, and priority invitations.', 'tcn-mlm' ),
			'features'    => [
				__( 'Higher discount rates with premium vendors.', 'tcn-mlm' ),
				__( 'Priority access to member-only campaigns and events.', 'tcn-mlm' ),
			],
			'benefits'    => [
				[
					'id'                 => 'platinum-sapphire-discount',
					'title'              => __( 'Sapphire loyalty savings', 'tcn-mlm' ),
					'description'        => __( 'Receive 5% off with Sapphire partners.', 'tcn-mlm' ),
					'discountPercentage' => 5,
				],
				[
					'id'                 => 'platinum-diamond-discount',
					'title'              => __( 'Premium Diamond savings', 'tcn-mlm' ),
					'description'        => __( 'Enjoy 10% off with Diamond partners.', 'tcn-mlm' ),
					'discountPercentage' => 10,
				],
			],
		],
		'black'    => [
			'label'       => __( 'Black Membership', 'tcn-mlm' ),
			'fee'         => 2000,
			'currency'    => 'THB',
			'interval'    => 'year',
			'description' => __( 'Top-tier access, maximum discounts, and concierge-level support.', 'tcn-mlm' ),
			'features'    => [
				__( 'Maximum partner discounts and VIP perks.', 'tcn-mlm' ),
				__( 'Exclusive campaigns with concierge support.', 'tcn-mlm' ),
			],
			'benefits'    => [
				[
					'id'                 => 'black-sapphire-discount',
					'title'              => __( 'Elite Sapphire savings', 'tcn-mlm' ),
					'description'        => __( 'Claim 10% off with Sapphire partners.', 'tcn-mlm' ),
					'discountPercentage' => 10,
				],
				[
					'id'                 => 'black-diamond-discount',
					'title'              => __( 'Diamond elite savings', 'tcn-mlm' ),
					'description'        => __( 'Access 20% savings with Diamond partners.', 'tcn-mlm' ),
					'discountPercentage' => 20,
				],
			],
		],
	];
}

function tcn_mlm_get_levels(): array {
	$option  = get_option( 'tcn_mlm_levels', [] );
	$levels  = isset( $option['levels'] ) && is_array( $option['levels'] ) ? $option['levels'] : [];
	$defaults = tcn_mlm_default_levels();

	foreach ( $defaults as $key => $default ) {
		if ( ! isset( $levels[ $key ] ) || ! is_array( $levels[ $key ] ) ) {
			$levels[ $key ] = $default;
			continue;
		}

		$levels[ $key ] = array_merge( $default, $levels[ $key ] );
	}

	return $levels;
}

function tcn_mlm_get_level_config( string $level ): array {
	$levels = tcn_mlm_get_levels();

	return $levels[ $level ] ?? [];
}

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
	tcn_mlm_ensure_membership_category();
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
	$stored_levels = get_option( 'tcn_mlm_levels', [] );
	$merged_levels = [
		'default' => 'blue',
		'levels'  => tcn_mlm_get_levels(),
	];

	if ( is_array( $stored_levels ) ) {
		$merged_levels = array_merge( $stored_levels, $merged_levels );
		$merged_levels['levels'] = array_merge( tcn_mlm_get_levels(), $stored_levels['levels'] ?? [] );
	}

	update_option( 'tcn_mlm_levels', $merged_levels, false );

	if ( false === get_option( 'tcn_mlm_general', false ) ) {
		update_option(
			'tcn_mlm_general',
			[
				'default_sponsor_id' => '',
				'currency'           => 'THB',
			],
			false
		);
	}
}

function tcn_mlm_seed_membership_products(): void {
	if ( ! class_exists( '\\WC_Product' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	tcn_mlm_ensure_membership_category();

	$levels = tcn_mlm_get_levels();

	foreach ( $levels as $slug => $config ) {
		tcn_mlm_ensure_membership_product( sanitize_key( $slug ), $config );
	}
}

function tcn_mlm_ensure_membership_category(): void {
	if ( ! function_exists( 'wp_insert_term' ) || ! function_exists( 'get_term_by' ) ) {
		return;
	}

	$stored_id = (int) get_option( 'tcn_mlm_membership_category_id', 0 );

	if ( $stored_id > 0 ) {
		$stored_term = get_term( $stored_id, 'product_cat' );
		if ( $stored_term && ! is_wp_error( $stored_term ) ) {
			return;
		}
	}

	$term = get_term_by( 'slug', 'memberships', 'product_cat' );

	if ( ! $term || is_wp_error( $term ) ) {
		$term = wp_insert_term(
			__( 'Memberships', 'tcn-mlm' ),
			'product_cat',
			[
				'slug'        => 'memberships',
				'description' => __( 'Membership products that unlock network tiers.', 'tcn-mlm' ),
			]
		);
	}

	if ( is_wp_error( $term ) ) {
		return;
	}

	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term->term_id );

	update_option( 'tcn_mlm_membership_category_id', $term_id, false );
}

function tcn_mlm_ensure_membership_product( string $level, array $config ): void {
	$product = tcn_mlm_find_product_by_level( $level );

	if ( ! $product ) {
		$label = isset( $config['label'] ) ? (string) $config['label'] : ucfirst( $level );
		$product = tcn_mlm_find_product_by_title( $label );
	}

	$price = isset( $config['fee'] ) ? (float) $config['fee'] : 0.0;

	if ( ! $product ) {
		$product = class_exists( '\WC_Product_Simple' )
			? new WC_Product_Simple()
			: new WC_Product();
		$product->set_name( wp_strip_all_tags( isset( $config['label'] ) ? (string) $config['label'] : ucfirst( $level ) ) );
		$product->set_slug( sanitize_title( 'tcn-mlm-' . $level ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_manage_stock( false );
		$product->set_sold_individually( true );
		$product->set_virtual( true );
	}

	$product->set_price( $price );
	$product->set_regular_price( $price );

	$product->update_meta_data( '_tcn_membership_level', $level );

	$category_id = (int) get_option( 'tcn_mlm_membership_category_id', 0 );

	if ( $category_id > 0 ) {
		wp_set_object_terms( $product->get_id(), [ $category_id ], 'product_cat', true );
		$uncategorized = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		if ( $uncategorized && ! is_wp_error( $uncategorized ) ) {
			wp_remove_object_terms( $product->get_id(), (int) $uncategorized->term_id, 'product_cat' );
		}
	}

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
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

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
