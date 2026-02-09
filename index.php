<?php
/**
 * Plugin Name:       Custom Health Endpoint
 * Plugin URI:        https://ambiscale.com/
 * Description:       Implementation of a /health endpoint for checking site and files status.
 * Version:           1.0.2
 * Author:            ambiscale
 * Author URI:        https://ambiscale.com/
 * License:           GPLv3
 */


if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'CUSTOM_HEALTH_ENDPOINT_VERSION', '1.0.2' );

if ( ! defined( 'AMBI_HEALTH_ENDPOINT' ) ) {
	define( 'AMBI_HEALTH_ENDPOINT', 'health' );
}

require_once __DIR__ . '/settings-page.php';

/**
 * Init hook to register rewrite rules.
 */
function custom_health_endpoint_init() {
	$endpoint = '^' . AMBI_HEALTH_ENDPOINT . '$';
	add_rewrite_rule( $endpoint, 'index.php?health_check=true', 'top' );
}
add_action( 'init', 'custom_health_endpoint_init' );

/**
 * Activation hook to register rewrite rules.
 */
function custom_health_endpoint_activate() {
	$endpoint = '^' . AMBI_HEALTH_ENDPOINT . '$';
	add_rewrite_rule( $endpoint, 'index.php?health_check=true', 'top' );
	flush_rewrite_rules();
}

/**
 * Deactivation hook to clear rewrite rules.
 */
function custom_health_endpoint_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'custom_health_endpoint_activate' );
register_deactivation_hook( __FILE__, 'custom_health_endpoint_deactivate' );

/**
 * Add health_check to query vars.
 */
function custom_health_add_query_vars( $public_query_vars ) {
	$public_query_vars[] = 'health_check';
	$public_query_vars[] = 'action';
	return $public_query_vars;
}
add_filter( 'query_vars', 'custom_health_add_query_vars' );

/**
 * Handle the health endpoint requests.
 */
function custom_health_endpoint_handler() {
	if ( get_query_var( 'health_check' ) === 'true' ) {
		$action = get_query_var( 'action' );

		$paths = get_option( 'custom_health_paths', array() );

		if ( $action && isset( $paths[ $action ] ) ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
			$file_url = $paths[ $action ];

			$response = wp_remote_get( $file_url, array( 'sslverify' => false ) );

			if ( is_wp_error( $response ) ) {
				status_header( 500 );
				echo "Error fetching URL for action '$action'.";
				exit;
			}

			$status_code = wp_remote_retrieve_response_code( $response );

			if ( $status_code === 200 ) {
				status_header( 200 );
				exit;
			} else {
				status_header( 500 );
				echo "Error - Action '$action' returned status code: $status_code";
				exit;
			}
		}

		custom_health_base_check();
	}
}


add_action( 'template_redirect', 'custom_health_endpoint_handler', 0 );


/**
 * Base health check: Checks for DB connection and fatal errors.
 */
function custom_health_base_check() {
	global $wpdb;
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Age: 0' );
	// Check database connection
	if ( ! $wpdb->check_connection( false ) ) {
		status_header( 500 );
		wp_die( 'No DB connection' );
	}
	status_header( 200 );
	exit;
}
