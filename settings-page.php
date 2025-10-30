<?php
/**
 * Add settings page to the dashboard.
 */
function custom_health_add_admin_menu() {
	add_options_page( 'Custom Health Endpoint', 'Health Endpoint', 'manage_options', 'custom_health_endpoint', 'custom_health_settings_page' );
}
add_action( 'admin_menu', 'custom_health_add_admin_menu' );

/**
 * Render the settings page.
 */
function custom_health_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['custom_health_paths'] ) ) {
		$paths        = explode( "\n", sanitize_textarea_field( $_POST['custom_health_paths'] ) );
		$parsed_paths = array();
		foreach ( $paths as $path ) {
			[$action, $file_path] = array_map( 'trim', explode( ':', $path, 2 ) );
			if ( $action && $file_path ) {
				$parsed_paths[ $action ] = $file_path;
			}
		}
		update_option( 'custom_health_paths', $parsed_paths );
	}

	$paths            = get_option( 'custom_health_paths', array() );
	$textarea_content = '';
	foreach ( $paths as $action => $file_path ) {
		$textarea_content .= "$action: $file_path\n";
	}

	echo '<div class="wrap">';
	echo '<h1>Custom Health Endpoint Settings</h1>';
	echo '<form method="POST">';
	echo '<label for="custom_health_paths">Define actions and file paths (one per line, format: action:path):</label><br>';
	echo '<textarea name="custom_health_paths" id="custom_health_paths" rows="10" cols="150">' . esc_textarea( trim( $textarea_content ) ) . '</textarea><br><br>';
	echo '<input type="submit" value="Save Changes" class="button button-primary">';
	echo '</form>';
	echo '</div>';
}
?>