<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\Frontend;

use UpcomingCalendarEvents\API\EventFetcher;

/**
 * Handles the AJAX request that returns rendered event HTML.
 *
 * Registered for both logged-in (wp_ajax_) and logged-out (wp_ajax_nopriv_)
 * users so any visitor can see events on the front-end.
 */
final class AjaxHandler {

	private const ACTION = 'uce_fetch_events';

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION,        [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * Validate the request, fetch events, return HTML.
	 */
	public function handle(): void {
		// CSRF check.
		if ( ! check_ajax_referer( 'uce_fetch_events', 'nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Security check failed.', 'upcoming-calendar-events' ) ],
				403
			);
		}

		// --- Validate & sanitise inputs -------------------------------------

		$calendar_id = sanitize_text_field( wp_unslash( $_POST['calendar_id'] ?? '' ) );
		if ( empty( $calendar_id ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Missing calendar_id parameter.', 'upcoming-calendar-events' ) ],
				400
			);
		}

		$count = isset( $_POST['count'] ) ? max( 1, min( 100, (int) $_POST['count'] ) ) : 5;
		$days  = isset( $_POST['days_ahead'] ) ? max( 1, (int) $_POST['days_ahead'] ) : 30;
		$show_desc = ! empty( $_POST['show_description'] ) && '1' === $_POST['show_description'];

		// --- Fetch events ---------------------------------------------------

		$fetcher = new EventFetcher();
		$result  = $fetcher->fetch( $calendar_id, $count, $days );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[ 'message' => $result->get_error_message() ],
				500
			);
		}

		// --- Render HTML ----------------------------------------------------

		$html = $this->render_events( $result, $show_desc );

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * Turn a normalised event array into semantic HTML.
	 *
	 * @param array $events    Array of normalised event data.
	 * @param bool  $show_desc Whether to include event descriptions.
	 * @return string
	 */
	private function render_events( array $events, bool $show_desc ): string {
		if ( empty( $events ) ) {
			return sprintf(
				'<p class="uce-no-events">%s</p>',
				esc_html__( 'No upcoming events found.', 'upcoming-calendar-events' )
			);
		}

		ob_start();
		?>
		<ol class="uce-events-list">
			<?php foreach ( $events as $event ) : ?>
				<?php
				$start_label = $this->format_date( $event['start'], $event['all_day'] );
				$end_label   = $this->format_date( $event['end'], $event['all_day'] );
				?>
				<li class="uce-event<?php echo $event['all_day'] ? ' uce-event--all-day' : ''; ?>">
					<article>
						<header class="uce-event__header">
							<?php if ( ! empty( $event['url'] ) ) : ?>
								<h3 class="uce-event__title">
									<a href="<?php echo esc_url( $event['url'] ); ?>" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html( $event['title'] ); ?>
									</a>
								</h3>
							<?php else : ?>
								<h3 class="uce-event__title"><?php echo esc_html( $event['title'] ); ?></h3>
							<?php endif; ?>

							<div class="uce-event__meta">
								<time class="uce-event__start" datetime="<?php echo esc_attr( $event['start'] ); ?>">
									<?php echo esc_html( $start_label ); ?>
								</time>
								<?php if ( $end_label && $end_label !== $start_label ) : ?>
									<span class="uce-event__separator" aria-hidden="true"> – </span>
									<time class="uce-event__end" datetime="<?php echo esc_attr( $event['end'] ); ?>">
										<?php echo esc_html( $end_label ); ?>
									</time>
								<?php endif; ?>
							</div>

							<?php if ( ! empty( $event['location'] ) ) : ?>
								<p class="uce-event__location">
									<span class="screen-reader-text"><?php esc_html_e( 'Location:', 'upcoming-calendar-events' ); ?></span>
									<?php echo esc_html( $event['location'] ); ?>
								</p>
							<?php endif; ?>
						</header>

						<?php if ( $show_desc && ! empty( $event['description'] ) ) : ?>
							<div class="uce-event__description">
								<?php echo wp_kses_post( $event['description'] ); ?>
							</div>
						<?php endif; ?>
					</article>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Format a date/datetime string for display.
	 *
	 * @param string $raw_date  ISO 8601 date or datetime string.
	 * @param bool   $all_day   True when this is a date-only (all-day) event.
	 * @return string           Localised, human-readable string.
	 */
	private function format_date( string $raw_date, bool $all_day ): string {
		if ( empty( $raw_date ) ) {
			return '';
		}

		try {
			$dt     = new \DateTimeImmutable( $raw_date );
			$format = $all_day
				? get_option( 'date_format', 'F j, Y' )
				: get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' );

			return wp_date( $format, $dt->getTimestamp() );
		} catch ( \Exception ) {
			return esc_html( $raw_date );
		}
	}
}
