<?php
/**
 * Template: Admin Settings Page
 *
 * Variables available from SettingsPage::render():
 *   @var array  $settings      Current plugin settings.
 *   @var bool   $saved         True if just saved successfully.
 *   @var bool   $flushed       True if cache was just flushed.
 *   @var string $api_error     API validation error message (may be empty).
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap uce-settings-wrap">
	<h1><?php esc_html_e( 'Calendar Events Settings', 'upcoming-calendar-events' ); ?></h1>

	<?php if ( $saved && ! $api_error ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved and API key validated successfully.', 'upcoming-calendar-events' ); ?></p>
		</div>
	<?php elseif ( $saved && $api_error ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php esc_html_e( 'Settings saved, but the API key could not be validated:', 'upcoming-calendar-events' ); ?>
				<strong><?php echo esc_html( $api_error ); ?></strong>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $flushed ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Cache cleared successfully.', 'upcoming-calendar-events' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Settings form -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="uce_save_settings">
		<?php wp_nonce_field( 'uce_save_settings', 'uce_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>

				<!-- Google API Key -->
				<tr>
					<th scope="row">
						<label for="uce_api_key">
							<?php esc_html_e( 'Google API Key', 'upcoming-calendar-events' ); ?>
							<span class="description"><?php esc_html_e( '(required)', 'upcoming-calendar-events' ); ?></span>
						</label>
					</th>
					<td>
						<input
							type="password"
							id="uce_api_key"
							name="uce_api_key"
							value="<?php echo esc_attr( $settings['api_key'] ); ?>"
							class="regular-text"
							autocomplete="off"
						>
						<p class="description">
							<?php
							printf(
								/* translators: %s: link to Google Console */
								esc_html__( 'Create a key at %s. Enable the Google Calendar API for your project.', 'upcoming-calendar-events' ),
								'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>'
							);
							?>
						</p>
					</td>
				</tr>

				<!-- Cache Duration -->
				<tr>
					<th scope="row">
						<label for="uce_cache_duration">
							<?php esc_html_e( 'Cache Duration (minutes)', 'upcoming-calendar-events' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="uce_cache_duration"
							name="uce_cache_duration"
							value="<?php echo esc_attr( (string) $settings['cache_duration'] ); ?>"
							class="small-text"
							min="1"
							max="1440"
						>
						<p class="description">
							<?php esc_html_e( 'How long to cache API responses (1–1440 minutes). Default: 60.', 'upcoming-calendar-events' ); ?>
						</p>
					</td>
				</tr>

				<!-- Default Event Count -->
				<tr>
					<th scope="row">
						<label for="uce_default_count">
							<?php esc_html_e( 'Default Number of Events', 'upcoming-calendar-events' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="uce_default_count"
							name="uce_default_count"
							value="<?php echo esc_attr( (string) $settings['default_count'] ); ?>"
							class="small-text"
							min="1"
							max="100"
						>
						<p class="description">
							<?php esc_html_e( 'Used when the shortcode is called without a count= attribute. Default: 5.', 'upcoming-calendar-events' ); ?>
						</p>
					</td>
				</tr>

			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save and Validate', 'upcoming-calendar-events' ); ?>
			</button>
		</p>
	</form>

	<hr>

	<!-- Manual cache flush -->
	<h2><?php esc_html_e( 'Cache Management', 'upcoming-calendar-events' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Force-refresh all cached calendar data immediately. Useful after adding events to your calendar.', 'upcoming-calendar-events' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="uce_flush_cache">
		<?php wp_nonce_field( 'uce_flush_cache', 'uce_flush_nonce' ); ?>
		<button type="submit" class="button button-secondary">
			<?php esc_html_e( 'Clear Cache Now', 'upcoming-calendar-events' ); ?>
		</button>
	</form>

	<hr>

	<!-- Shortcode reference -->
	<h2><?php esc_html_e( 'Shortcode Usage', 'upcoming-calendar-events' ); ?></h2>
	<p><?php esc_html_e( 'Add the following shortcode to any page or post:', 'upcoming-calendar-events' ); ?></p>
	<pre class="uce-code-block"><code>[upcoming_events
    count="10"
    days_ahead="30"
    show_description="true"
    google_calendar_id="your-calendar-id@group.calendar.google.com"]</code></pre>

	<table class="widefat striped uce-attr-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Attribute', 'upcoming-calendar-events' ); ?></th>
				<th><?php esc_html_e( 'Required', 'upcoming-calendar-events' ); ?></th>
				<th><?php esc_html_e( 'Default', 'upcoming-calendar-events' ); ?></th>
				<th><?php esc_html_e( 'Description', 'upcoming-calendar-events' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><code>google_calendar_id</code></td>
				<td><?php esc_html_e( 'Yes', 'upcoming-calendar-events' ); ?></td>
				<td>—</td>
				<td><?php esc_html_e( 'The Google Calendar ID (e.g. name@group.calendar.google.com)', 'upcoming-calendar-events' ); ?></td>
			</tr>
			<tr>
				<td><code>days_ahead</code></td>
				<td><?php esc_html_e( 'Yes', 'upcoming-calendar-events' ); ?></td>
				<td>—</td>
				<td><?php esc_html_e( 'How many days into the future to look for events', 'upcoming-calendar-events' ); ?></td>
			</tr>
			<tr>
				<td><code>count</code></td>
				<td><?php esc_html_e( 'No', 'upcoming-calendar-events' ); ?></td>
				<td><?php echo esc_html( (string) $settings['default_count'] ); ?></td>
				<td><?php esc_html_e( 'Maximum number of events to display', 'upcoming-calendar-events' ); ?></td>
			</tr>
			<tr>
				<td><code>show_description</code></td>
				<td><?php esc_html_e( 'No', 'upcoming-calendar-events' ); ?></td>
				<td>false</td>
				<td><?php esc_html_e( 'Whether to show event descriptions (true/false)', 'upcoming-calendar-events' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>
