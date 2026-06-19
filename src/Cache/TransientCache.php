<?php

declare( strict_types=1 );

namespace UpcomingCalendarEvents\Cache;

/**
 * Manages WP Transient caching for Google Calendar API responses.
 *
 * All cache keys are prefixed with "uce_" so they can be flushed
 * collectively without touching unrelated transients.
 */
final class TransientCache {

	private const PREFIX = 'uce_';

	/**
	 * Generate a deterministic cache key from the request parameters.
	 *
	 * @param string $calendar_id
	 * @param int    $max_results
	 * @param int    $days_ahead
	 */
	public function make_key( string $calendar_id, int $max_results, int $days_ahead ): string {
		$raw = $calendar_id . '_' . $max_results . '_' . $days_ahead;
		// md5 keeps the key short and safe for the 172-char transient limit.
		return self::PREFIX . md5( $raw );
	}

	/**
	 * Retrieve a cached value, or return null on a miss.
	 *
	 * @param string $key
	 * @return array|null
	 */
	public function get( string $key ): ?array {
		$cached = get_transient( $key );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $key
	 * @param array  $data
	 * @param int    $duration_minutes
	 */
	public function set( string $key, array $data, int $duration_minutes ): void {
		// We also store the key name so flush_all() can find it.
		$this->register_key( $key );
		set_transient( $key, $data, $duration_minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Delete all UCE transients. Called on settings save and manual flush.
	 */
	public function flush_all(): void {
		$registry = $this->get_registry();

		foreach ( $registry as $key ) {
			delete_transient( $key );
		}

		delete_option( 'uce_transient_registry' );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function register_key( string $key ): void {
		$registry   = $this->get_registry();
		$registry[] = $key;
		update_option( 'uce_transient_registry', array_unique( $registry ), false );
	}

	/**
	 * @return string[]
	 */
	private function get_registry(): array {
		$registry = get_option( 'uce_transient_registry', [] );
		return is_array( $registry ) ? $registry : [];
	}
}
