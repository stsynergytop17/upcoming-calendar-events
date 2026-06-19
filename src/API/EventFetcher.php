<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\API;

use UpcomingCalendarEvents\Admin\SettingsPage;
use UpcomingCalendarEvents\Cache\TransientCache;

/**
 * Orchestrates API calls + caching for calendar event retrieval.
 */
final class EventFetcher {

	private TransientCache $cache;
	private array $settings;

	public function __construct() {
		$this->cache    = new TransientCache();
		$this->settings = SettingsPage::get_settings();
	}

	/**
	 * Return events for the given parameters, using the cache when available.
	 *
	 * @param string $calendar_id
	 * @param int    $count
	 * @param int    $days_ahead
	 *
	 * @return array|\WP_Error
	 */
	public function fetch( string $calendar_id, int $count, int $days_ahead ): array|\WP_Error {
		if ( empty( $this->settings['api_key'] ) ) {
			return new \WP_Error(
				'uce_no_api_key',
				__( 'Google API key is not configured. Please visit Settings → Calendar Events.', 'upcoming-calendar-events' )
			);
		}

		$cache_key = $this->cache->make_key( $calendar_id, $count, $days_ahead );
		$cached    = $this->cache->get( $cache_key );

		if ( null !== $cached ) {
			return $cached;
		}

		$client = new GoogleCalendarClient( $this->settings['api_key'] );
		$result = $client->get_events( $calendar_id, $count, $days_ahead );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->cache->set( $cache_key, $result, $this->settings['cache_duration'] );

		return $result;
	}
}
