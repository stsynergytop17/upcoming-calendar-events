<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\Frontend;

use UpcomingCalendarEvents\Admin\SettingsPage;

/**
 * Registers the [upcoming_events] shortcode.
 *
 * The shortcode itself renders a lightweight placeholder div; the actual
 * event list is loaded via AJAX so caching at the page level doesn't
 * interfere with dynamic event data.
 *
 * Usage:
 *   [upcoming_events
 *       count="10"
 *       days_ahead="30"
 *       show_description="true"
 *       google_calendar_id="abc@group.calendar.google.com"]
 */
final class Shortcode {

	public function register(): void {
		add_shortcode( 'upcoming_events', [ $this, 'render' ] );
	}

	/**
	 * Output the AJAX placeholder container.
	 *
	 * @param array|string $atts  Raw shortcode attributes.
	 * @return string             HTML string (never echo directly).
	 */
	public function render( array|string $atts ): string {
		$settings = SettingsPage::get_settings();

		$atts = shortcode_atts(
			[
				'count'              => (string) $settings['default_count'],
				'days_ahead'         => '',           // mandatory — validated below
				'show_description'   => 'false',
				'google_calendar_id' => '',           // mandatory — validated below
			],
			(array) $atts,
			'upcoming_events'
		);

		// --- Validate mandatory attributes -----------------------------------

		if ( empty( $atts['google_calendar_id'] ) ) {
			return $this->render_error(
				__( 'Shortcode error: <code>google_calendar_id</code> attribute is required.', 'upcoming-calendar-events' )
			);
		}

		if ( ! is_numeric( $atts['days_ahead'] ) || (int) $atts['days_ahead'] < 1 ) {
			return $this->render_error(
				__( 'Shortcode error: <code>days_ahead</code> must be a positive integer.', 'upcoming-calendar-events' )
			);
		}

		// --- Sanitise values -------------------------------------------------

		$calendar_id      = sanitize_text_field( $atts['google_calendar_id'] );
		$count            = max( 1, min( 100, (int) $atts['count'] ) );
		$days_ahead       = max( 1, (int) $atts['days_ahead'] );
		$show_description = filter_var( $atts['show_description'], FILTER_VALIDATE_BOOLEAN );

		// --- Enqueue assets on demand ----------------------------------------

		wp_enqueue_style( 'uce-styles' );
		wp_enqueue_script( 'uce-frontend' );

		// --- Render placeholder ----------------------------------------------

		$container_id = 'uce-' . esc_attr( substr( md5( $calendar_id . $count . $days_ahead ), 0, 8 ) );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $container_id ); ?>"
			class="uce-events-wrapper"
			data-calendar-id="<?php echo esc_attr( $calendar_id ); ?>"
			data-count="<?php echo esc_attr( (string) $count ); ?>"
			data-days-ahead="<?php echo esc_attr( (string) $days_ahead ); ?>"
			data-show-description="<?php echo esc_attr( $show_description ? '1' : '0' ); ?>"
			aria-live="polite"
			aria-label="<?php esc_attr_e( 'Upcoming calendar events', 'upcoming-calendar-events' ); ?>"
		>
			<p class="uce-loading" aria-busy="true">
				<?php esc_html_e( 'Loading events…', 'upcoming-calendar-events' ); ?>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render an inline error message (only shown to editors/admins in context).
	 */
	private function render_error( string $message ): string {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return sprintf(
			'<p class="uce-error" role="alert">%s</p>',
			wp_kses( $message, [ 'code' => [] ] )
		);
	}
}
