<?php
/**
 * Plugin Name:       TCN MLM
 * Plugin URI:        https://github.com/GeorgeWebDevCy/tcn-mlm
 * Description:       Network marketing automation for WooCommerce memberships.
 * Version:           0.1.1
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
	define( 'TCN_MLM_VERSION', '0.1.1' );
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

	// @todo Wire up the service container once the implementation is available.
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
