<?php
/**
 * Uninstall hook — runs when the plugin is deleted from the WP admin.
 *
 * Removes all plugin options and cached transients.
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove settings.
delete_option( 'uce_settings' );

// Remove all registered transients.
$registry = get_option( 'uce_transient_registry', [] );
if ( is_array( $registry ) ) {
	foreach ( $registry as $key ) {
		delete_transient( $key );
	}
}

delete_option( 'uce_transient_registry' );
