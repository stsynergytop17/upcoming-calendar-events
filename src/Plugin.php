<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents;

use UpcomingCalendarEvents\Admin\SettingsPage;
use UpcomingCalendarEvents\Frontend\Shortcode;
use UpcomingCalendarEvents\Frontend\AjaxHandler;

/**
 * Central plugin class — wires up all subsystems.
 */
final class Plugin {

	/**
	 * Register hooks for every subsystem.
	 */
	public function init(): void {
		// Admin.
		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}

		// Shortcode (registered on both front and admin for preview).
		( new Shortcode() )->register();

		// AJAX handler (fires on both admin-ajax.php paths).
		( new AjaxHandler() )->register();

		// Enqueue front-end assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Enqueue CSS and JS for the front-end.
	 */
	public function enqueue_frontend_assets(): void {
		wp_register_style(
			'uce-styles',
			UCE_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			UCE_VERSION
		);

		wp_register_script(
			'uce-frontend',
			UCE_PLUGIN_URL . 'assets/js/frontend.js',
			[ 'jquery' ],
			UCE_VERSION,
			true
		);

		wp_localize_script(
			'uce-frontend',
			'uceData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'uce_fetch_events' ),
			]
		);
	}

	/**
	 * Runs once on plugin activation.
	 */
	public static function activate(): void {
		// Set default options if they don't exist yet.
		if ( ! get_option( 'uce_settings' ) ) {
			update_option(
				'uce_settings',
				[
					'api_key'        => '',
					'cache_duration' => 60,
					'default_count'  => 5,
				]
			);
		}
	}

	/**
	 * Runs once on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Intentionally empty — preserve settings on deactivation.
		// Settings are only removed on uninstall (see uninstall.php).
	}
}
