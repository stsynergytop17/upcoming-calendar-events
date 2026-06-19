<?php
/**
 * Plugin Name: Upcoming Calendar Events
 * Plugin URI:  https://github.com/stsynergytop17/upcoming-calendar-events.git
 * Description: Displays upcoming events from Google Calendar via a shortcode or Gutenberg block.
 * Version:     1.0.0
 * Text Domain: upcoming-calendar-events
 */

declare( strict_types=1 );

namespace UpcomingCalendarEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UCE_VERSION', '1.0.0' );
define( 'UCE_PLUGIN_FILE', __FILE__ );
define( 'UCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader (Composer PSR-4).
if ( file_exists( UCE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once UCE_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	// Fallback manual PSR-4 autoloader for environments without Composer.
	spl_autoload_register( function ( string $class ): void {
		$prefix    = 'UpcomingCalendarEvents\\';
		$base_dir  = UCE_PLUGIN_DIR . 'src/';
		$len       = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative  = substr( $class, $len );
		$file      = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	} );
}

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
add_action( 'plugins_loaded', function (): void {
	( new Plugin() )->init();
} );

register_activation_hook( __FILE__, [ Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] );
