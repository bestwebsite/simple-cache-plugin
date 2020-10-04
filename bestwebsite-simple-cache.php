<?php
/**
 * Plugin Name: bestwebsite Simple Cache
 * Plugin URI: https://github.com/bestwebsite/simple-cache-plugin
 * Description: A simple caching plugin that just works.
 * Author: bestwebsite
 * Version: 1.0
 * Text Domain: bestwebsite-bestwebsite-simple-cache
 * Domain Path: /languages
 * Author URI: http://bestwebsite.com
 *
 * @package  bestwebsite-simple-cache
 */

defined( 'ABSPATH' ) || exit;

define( 'bestwebsite_VERSION', '1.0' );
define( 'bestwebsite_PATH', dirname( __FILE__ ) );

$active_plugins = get_site_option( 'active_sitewide_plugins' );

if ( is_multisite() && isset( $active_plugins[ plugin_basename( __FILE__ ) ] ) ) {
	define( 'bestwebsite_IS_NETWORK', true );
} else {
	define( 'bestwebsite_IS_NETWORK', false );
}

require_once bestwebsite_PATH . '/inc/pre-wp-functions.php';
require_once bestwebsite_PATH . '/inc/functions.php';
require_once bestwebsite_PATH . '/inc/class-sc-notices.php';
require_once bestwebsite_PATH . '/inc/class-sc-settings.php';
require_once bestwebsite_PATH . '/inc/class-sc-config.php';
require_once bestwebsite_PATH . '/inc/class-sc-advanced-cache.php';
require_once bestwebsite_PATH . '/inc/class-sc-object-cache.php';
require_once bestwebsite_PATH . '/inc/class-sc-cron.php';

bestwebsite_Settings::factory();
bestwebsite_Advanced_Cache::factory();
bestwebsite_Object_Cache::factory();
bestwebsite_Cron::factory();
bestwebsite_Notices::factory();

/**
 * Load text domain
 *
 * @since 1.0
 */
function bestwebsite_load_textdomain() {

	load_plugin_textdomain( 'bestwebsite-simple-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'bestwebsite_load_textdomain' );


/**
 * Add settings link to plugin actions
 *
 * @param  array  $plugin_actions Each action is HTML.
 * @param  string $plugin_file Path to plugin file.
 * @since  1.0
 * @return array
 */
function bestwebsite_filter_plugin_action_links( $plugin_actions, $plugin_file ) {

	$new_actions = array();

	if ( basename( dirname( __FILE__ ) ) . '/bestwebsite-simple-cache.php' === $plugin_file ) {
		/* translators: Param 1 is link to settings page. */
		$new_actions['bestwebsite_settings'] = '<a href="' . esc_url( admin_url( 'options-general.php?page=bestwebsite-simple-cache' ) ) . '">' . esc_html__( 'Settings', 'bestwebsite-simple-cache' ) . '</a>';
	}

	return array_merge( $new_actions, $plugin_actions );
}
add_filter( 'plugin_action_links', 'bestwebsite_filter_plugin_action_links', 10, 2 );

/**
 * Clean up necessary files
 *
 * @param  bool $network Whether the plugin is network wide
 * @since 1.0
 */
function bestwebsite_deactivate( $network ) {
	if ( ! apply_filters( 'bestwebsite_disable_auto_edits', false ) ) {
		bestwebsite_Advanced_Cache::factory()->clean_up();
		bestwebsite_Advanced_Cache::factory()->toggle_caching( false );
		bestwebsite_Object_Cache::factory()->clean_up();
	}

	bestwebsite_Config::factory()->clean_up();

	bestwebsite_cache_flush( $network );
}
add_action( 'deactivate_' . plugin_basename( __FILE__ ), 'bestwebsite_deactivate' );

/**
 * Create config file
 *
 * @param  bool $network Whether the plugin is network wide
 * @since 1.0
 */
function bestwebsite_activate( $network ) {
	if ( $network ) {
		bestwebsite_Config::factory()->write( array(), true );
	} else {
		bestwebsite_Config::factory()->write( array() );
	}
}
add_action( 'activate_' . plugin_basename( __FILE__ ), 'bestwebsite_activate' );


