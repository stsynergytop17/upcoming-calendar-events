<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\API;

/**
 * Thin HTTP wrapper around the Google Calendar Events: list endpoint.
 *
 * @link https://developers.google.com/calendar/api/v3/reference/events/list
 */
final class GoogleCalendarClient {

	private const BASE_URL = 'https://www.googleapis.com/calendar/v3/calendars';

	public function __construct( private readonly string $api_key ) {}

	/**
	 * Fetch upcoming events from a Google Calendar.
	 *
	 * @param string $calendar_id  The calendar ID (e.g. "something@group.calendar.google.com").
	 * @param int    $max_results  Maximum number of events to return.
	 * @param int    $days_ahead   How many days into the future to query.
	 *
	 * @return array|\WP_Error Parsed event array or WP_Error on failure.
	 */
	public function get_events( string $calendar_id, int $max_results, int $days_ahead ): array|\WP_Error {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'uce_no_api_key', __( 'Google API key is not configured.', 'upcoming-calendar-events' ) );
		}

		
		
		$time_min = gmdate( 'Y-m-d\TH:i:s\Z' );
		$time_max = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( "+{$days_ahead} days" ) );

		$endpoint = sprintf(
			'%s/%s/events',
			self::BASE_URL,
			rawurlencode( $calendar_id )
		);

		$url = add_query_arg(
			[
				'key'          => $this->api_key,
				'timeMin'      => $time_min,
				'timeMax'      => $time_max,
				'maxResults'   => (int) $max_results,
				'singleEvents' => 'true',
				'orderBy'      => 'startTime',
			],
			$endpoint
		);
		// 		echo '<pre>';
// print_r($url);
// echo '</pre>';
// exit;
	
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 20,
			]
		);

		// Debug
		error_log( 'Google URL: ' . $url );
		error_log( 'Google Response: ' . wp_remote_retrieve_body( $response ) );


		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'uce_http_error',
				sprintf(
					/* translators: %s: network error message */
					__( 'Network error while fetching calendar events: %s', 'upcoming-calendar-events' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			$api_message = $data['error']['message'] ?? 'Unknown API error';
			return new \WP_Error(
				'uce_api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: API error message */
					__( 'Google Calendar API error (HTTP %1$d): %2$s', 'upcoming-calendar-events' ),
					$status_code,
					sanitize_text_field( $api_message )
				)
			);
		}

		if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
			return new \WP_Error(
				'uce_malformed_response',
				__( 'Unexpected response format from Google Calendar API.', 'upcoming-calendar-events' )
			);
		}

		return $this->parse_events( $data['items'] );
	}

	/**
	 * Lightweight validation — checks that the API key is usable.
	 * Uses the public calendar list endpoint; a 400 with a key-related
	 * message indicates a bad key; 403 indicates domain restrictions.
	 *
	 * @return true|\WP_Error
	 */
	public function validate_key(): true|\WP_Error {
		// Hit a known public calendar with a minimal query to test the key.
		$url = add_query_arg(
			[
				'key'        => $this->api_key,
				'maxResults' => 1,
			],
			self::BASE_URL . '/primary/events'
		);

		$response = wp_remote_get( $url, [ 'timeout' => 8 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );

		// 200 or 403 (forbidden but key is valid) both mean the key exists.
		if ( in_array( $status, [ 200, 403, 404 ], true ) ) {
			return true;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
// 		echo '<pre>';
// print_r($body);
// echo '</pre>';
// exit;
		$msg  = $data['error']['message'] ?? __( 'API key validation failed.', 'upcoming-calendar-events' );

		return new \WP_Error( 'uce_invalid_key', sanitize_text_field( $msg ) );
	}

	/**
	 * Normalise raw Google event items into a clean, consistent shape.
	 *
	 * @param array $items Raw items array from the API response.
	 * @return array<int, array{id: string, title: string, start: string, end: string, all_day: bool, description: string, location: string, url: string}>
	 */
	private function parse_events( array $items ): array {
		$events = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Determine all-day vs timed event.
			$all_day = isset( $item['start']['date'] ) && ! isset( $item['start']['dateTime'] );
			$start   = $all_day
				? ( $item['start']['date'] ?? '' )
				: ( $item['start']['dateTime'] ?? '' );
			$end     = $all_day
				? ( $item['end']['date'] ?? '' )
				: ( $item['end']['dateTime'] ?? '' );

			$events[] = [
				'id'          => sanitize_text_field( $item['id'] ?? '' ),
				'title'       => sanitize_text_field( $item['summary'] ?? __( '(No title)', 'upcoming-calendar-events' ) ),
				'start'       => sanitize_text_field( $start ),
				'end'         => sanitize_text_field( $end ),
				'all_day'     => $all_day,
				'description' => wp_kses_post( $item['description'] ?? '' ),
				'location'    => sanitize_text_field( $item['location'] ?? '' ),
				'url'         => esc_url_raw( $item['htmlLink'] ?? '' ),
			];
		}

		return $events;
	}
}
