<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\Admin;

use UpcomingCalendarEvents\Cache\TransientCache;
use UpcomingCalendarEvents\API\GoogleCalendarClient;

/**
 * Registers and renders the Settings → Calendar Events admin page.
 */
final class SettingsPage {

	private const OPTION_KEY  = 'uce_settings';
	private const MENU_SLUG   = 'uce-settings';
	private const NONCE_ACTION = 'uce_save_settings';
	private const NONCE_FIELD  = 'uce_settings_nonce';
	private const CACHE_NONCE_ACTION = 'uce_flush_cache';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_post_uce_save_settings', [ $this, 'handle_save' ] );
		add_action( 'admin_post_uce_flush_cache', [ $this, 'handle_flush_cache' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function add_menu_page(): void {
		add_options_page(
			__( 'Calendar Events Settings', 'upcoming-calendar-events' ),
			__( 'Calendar Events', 'upcoming-calendar-events' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render' ]
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'uce-admin',
			UCE_PLUGIN_URL . 'assets/css/admin.css',
			[],
			UCE_VERSION
		);
	}

	/**
	 * Handle the "Save and Validate" form submission.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'upcoming-calendar-events' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$api_key        = sanitize_text_field( wp_unslash( $_POST['uce_api_key'] ?? '' ) );
		$cache_duration = absint( $_POST['uce_cache_duration'] ?? 60 );
		$default_count  = absint( $_POST['uce_default_count'] ?? 5 );

		// Clamp sensible ranges.
		$cache_duration = max( 1, min( $cache_duration, 1440 ) ); // 1 min – 24 h
		$default_count  = max( 1, min( $default_count, 100 ) );

		// Validate the API key by making a lightweight test request.
		$validation_error = '';
		if ( ! empty( $api_key ) ) {
			$client = new GoogleCalendarClient( $api_key );
			$result = $client->validate_key();
			if ( is_wp_error( $result ) ) {
				$validation_error = $result->get_error_message();
			}
		}

		update_option(
			self::OPTION_KEY,
			[
				'api_key'        => $api_key,
				'cache_duration' => $cache_duration,
				'default_count'  => $default_count,
			]
		);

		// Flush all cached calendar data whenever settings change.
		( new TransientCache() )->flush_all();

		$query_args = [ 'page' => self::MENU_SLUG, 'saved' => '1' ];
		if ( $validation_error ) {
			$query_args['uce_error'] = urlencode( $validation_error );
		}

		wp_safe_redirect(
			add_query_arg( $query_args, admin_url( 'options-general.php' ) )
		);
		exit;
	}

	/**
	 * Handle the "Manual cache refresh" button.
	 */
	public function handle_flush_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'upcoming-calendar-events' ), 403 );
		}

		check_admin_referer( self::CACHE_NONCE_ACTION, 'uce_flush_nonce' );

		( new TransientCache() )->flush_all();

		wp_safe_redirect(
			add_query_arg( [ 'page' => self::MENU_SLUG, 'flushed' => '1' ], admin_url( 'options-general.php' ) )
		);
		exit;
	}

	/**
	 * Render the settings page HTML.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'upcoming-calendar-events' ) );
		}

		$settings = $this->get_settings();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$saved         = isset( $_GET['saved'] );
		$flushed       = isset( $_GET['flushed'] );
		$api_error_raw = isset( $_GET['uce_error'] ) ? sanitize_text_field( wp_unslash( $_GET['uce_error'] ) ) : '';
		$api_error     = $api_error_raw ? urldecode( $api_error_raw ) : '';
		// phpcs:enable

		include UCE_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Return current plugin settings with defaults.
	 *
	 * @return array{api_key: string, cache_duration: int, default_count: int}
	 */
	public static function get_settings(): array {
		$defaults = [
			'api_key'        => '',
			'cache_duration' => 60,
			'default_count'  => 5,
		];

		$saved = get_option( self::OPTION_KEY, [] );

		return wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );
	}
}
