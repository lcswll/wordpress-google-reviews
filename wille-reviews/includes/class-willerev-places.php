<?php
/**
 * Google Places API (New): fetches rating, review count and the latest reviews server-side and caches them.
 *
 * Visitors never talk to Google: the data is refreshed by WP-Cron (interval = cache duration) and kept in a
 * transient plus a backup copy that bridges API outages. Google's terms allow caching Places content for up to
 * 30 days, so the backup is never used once it is older than that.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Places API module.
 */
class WILLEREV_Places {

	/**
	 * Transient with the current data.
	 */
	const CACHE = 'willerev_data';

	/**
	 * Option with the last successful response (fallback during API errors).
	 */
	const BACKUP = 'willerev_backup';

	/**
	 * Option with the result of the last API call: array{state:string,message:string,time:int}.
	 */
	const STATUS = 'willerev_status';

	/**
	 * Transient that serializes refreshes on a cache miss and backs off after an error.
	 */
	const LOCK = 'willerev_refresh_lock';

	/**
	 * Cron hook of the background refresh.
	 */
	const CRON = 'willerev_refresh';

	/**
	 * Place Details endpoint of the Places API (New).
	 */
	const ENDPOINT = 'https://places.googleapis.com/v1/places/';

	/**
	 * Requested fields (the field mask decides what Google bills).
	 */
	const FIELDS = 'id,displayName,rating,userRatingCount,reviews,googleMapsUri';

	/**
	 * Longest time Places content may be cached (Google Maps Platform terms).
	 */
	const MAX_AGE = 30 * DAY_IN_SECONDS;

	/**
	 * Wait this long before calling the API again after a failed call.
	 */
	const ERROR_BACKOFF = 10 * MINUTE_IN_SECONDS;

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- Interval = cache duration (setting, sanitized to at least 1 hour).
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		add_action( self::CRON, array( __CLASS__, 'cron_refresh' ) );
		add_action( 'admin_init', array( __CLASS__, 'schedule' ) );
		add_action( 'update_option_willerev_settings', array( __CLASS__, 'settings_changed' ), 10, 2 );
	}

	/**
	 * Cron interval matching the cache duration.
	 *
	 * @param array<string,array<string,mixed>> $schedules Registered schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['willerev_interval'] = array(
			'interval' => self::cache_hours() * HOUR_IN_SECONDS,
			'display'  => __( 'Wille Reviews refresh interval', 'wille-reviews' ),
		);
		return $schedules;
	}

	/**
	 * Cache duration in hours (1–168).
	 *
	 * @return int
	 */
	public static function cache_hours() {
		return min( 168, max( 1, (int) willerev()->settings()['cache_hours'] ) );
	}

	/**
	 * Plan the background refresh (only while a place is connected).
	 *
	 * @return void
	 */
	public static function schedule() {
		if ( ! self::is_configured() ) {
			self::unschedule();
			return;
		}
		if ( false === wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'willerev_interval', self::CRON );
		}
	}

	/**
	 * Remove the background refresh.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/**
	 * Cron callback.
	 *
	 * @return void
	 */
	public static function cron_refresh() {
		self::refresh();
	}

	/**
	 * After a settings save: a new place, key, language or interval invalidates the cache and the schedule.
	 *
	 * @param mixed $old_value Previous settings.
	 * @param mixed $new_value New settings.
	 * @return void
	 */
	public static function settings_changed( $old_value, $new_value ) {
		willerev()->flush_settings_cache();
		$old     = is_array( $old_value ) ? $old_value : array();
		$new     = is_array( $new_value ) ? $new_value : array();
		$changed = false;
		foreach ( array( 'api_key', 'place_id', 'language', 'cache_hours', 'original_text', 'hide_avatars' ) as $key ) {
			if ( ( $old[ $key ] ?? null ) !== ( $new[ $key ] ?? null ) ) {
				$changed = true;
			}
		}
		if ( ! $changed ) {
			return;
		}
		delete_transient( self::CACHE );
		delete_transient( self::LOCK );
		if ( ( $old['place_id'] ?? '' ) !== ( $new['place_id'] ?? '' ) ) {
			// Another business: its predecessor's reviews must never show up as a fallback.
			delete_option( self::BACKUP );
		}
		self::unschedule();
		self::schedule();
	}

	/**
	 * Whether API key and place ID are set.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		$settings = willerev()->settings();
		return '' !== (string) $settings['api_key'] && '' !== (string) $settings['place_id'];
	}

	/**
	 * Current data (cache → live refresh → backup), or null when nothing is available.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function data() {
		if ( ! self::is_configured() ) {
			return null;
		}
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) && isset( $cached['rating'] ) ) {
			return $cached;
		}
		// Cache miss (first view, flushed, cron late): one request refreshes, concurrent ones use the backup.
		if ( ! get_transient( self::LOCK ) ) {
			$fresh = self::refresh();
			if ( null !== $fresh ) {
				return $fresh;
			}
		}
		return self::backup();
	}

	/**
	 * Last successful response, if it is not older than Google allows.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function backup() {
		$backup = get_option( self::BACKUP );
		if ( ! is_array( $backup ) || ! isset( $backup['rating'], $backup['fetched_at'] ) ) {
			return null;
		}
		if ( time() - (int) $backup['fetched_at'] > self::MAX_AGE ) {
			return null;
		}
		return $backup;
	}

	/**
	 * Fetch from Google, mirror the profile photos and write cache + backup.
	 *
	 * @return array<string,mixed>|null Fresh data, or null on error (see status()).
	 */
	public static function refresh() {
		if ( ! self::is_configured() ) {
			return null;
		}
		set_transient( self::LOCK, 1, MINUTE_IN_SECONDS );

		$data = self::fetch();
		if ( is_wp_error( $data ) ) {
			self::log( 'error', $data->get_error_message() );
			set_transient( self::LOCK, 1, self::ERROR_BACKOFF );
			return null;
		}

		$hide_avatars = ! empty( willerev()->settings()['hide_avatars'] );
		foreach ( $data['reviews'] as $index => $review ) {
			$data['reviews'][ $index ]['avatar'] = $hide_avatars ? '' : WILLEREV_Avatars::mirror( (string) $review['avatar_remote'] );
			unset( $data['reviews'][ $index ]['avatar_remote'] );
		}
		WILLEREV_Avatars::cleanup( wp_list_pluck( $data['reviews'], 'avatar' ) );

		$data['fetched_at'] = time();
		// +1 h so the cron renews the transient before it expires – no visitor waits for the API.
		set_transient( self::CACHE, $data, ( self::cache_hours() + 1 ) * HOUR_IN_SECONDS );
		update_option( self::BACKUP, $data, false );
		delete_transient( self::LOCK );

		/* translators: 1: number of reviews on Google, 2: average rating */
		self::log( 'ok', sprintf( __( '%1$s reviews, average %2$s', 'wille-reviews' ), number_format_i18n( (int) $data['count'] ), number_format_i18n( (float) $data['rating'], 1 ) ) );

		/**
		 * Fires after the reviews were refreshed from Google.
		 *
		 * @param array<string,mixed> $data Normalized place data.
		 */
		do_action( 'willerev_refreshed', $data );

		return $data;
	}

	/**
	 * Place Details request.
	 *
	 * @return array<string,mixed>|WP_Error Normalized data.
	 */
	protected static function fetch() {
		$settings = willerev()->settings();
		$url      = add_query_arg(
			'languageCode',
			self::language_code( (string) $settings['language'], get_locale() ),
			self::ENDPOINT . rawurlencode( (string) $settings['place_id'] )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'X-Goog-Api-Key'   => (string) $settings['api_key'],
					'X-Goog-FieldMask' => self::FIELDS,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $body ) ) {
			return new WP_Error( 'willerev_api', self::error_message( $code, $body ) );
		}

		return self::normalize( $body, (string) $settings['place_id'], ! empty( $settings['original_text'] ) );
	}

	/**
	 * Readable error from a failed API response (pure).
	 *
	 * @param int   $code HTTP status.
	 * @param mixed $body Decoded body.
	 * @return string
	 */
	public static function error_message( $code, $body ) {
		$error   = is_array( $body ) && isset( $body['error'] ) && is_array( $body['error'] ) ? $body['error'] : array();
		$status  = isset( $error['status'] ) ? (string) $error['status'] : '';
		$message = isset( $error['message'] ) ? (string) $error['message'] : '';
		$reason  = '';
		foreach ( isset( $error['details'] ) && is_array( $error['details'] ) ? $error['details'] : array() as $detail ) {
			if ( is_array( $detail ) && isset( $detail['reason'] ) && '' === $reason ) {
				$reason = (string) $detail['reason'];
			}
		}
		$text = trim( 'HTTP ' . $code . ' ' . $status . ( '' !== $message ? ': ' . $message : '' ) . ( '' !== $reason ? ' (' . $reason . ')' : '' ) );
		return sanitize_text_field( $text );
	}

	/**
	 * What to do about a failed request, in plain language (pure; '' when the error is unknown).
	 *
	 * @param string $message Stored error message (see error_message() or a WP_Error from the HTTP API).
	 * @return string
	 */
	public static function hint( $message ) {
		$rules = array(
			'/REFERRER_BLOCKED|referer/i'                  => __( 'The API key is restricted to websites (HTTP referrers). The plugin calls Google from your server, which sends no referrer. In the Google Cloud Console, set the key\'s application restriction to "None" or to your server\'s IP address, and restrict it to the Places API (New) instead.', 'wille-reviews' ),
			'/IP_ADDRESS_BLOCKED|IP address restriction/i' => __( 'The API key is restricted to IP addresses that do not include your web server. Add your server\'s outgoing IP address to the key in the Google Cloud Console, or remove the IP restriction.', 'wille-reviews' ),
			'/SERVICE_BLOCKED|not authorized to use this/i' => __( 'The key\'s API restrictions do not include the Places API (New). In the Google Cloud Console, edit the key and add "Places API (New)" to the allowed APIs.', 'wille-reviews' ),
			'/BILLING|billing/i'                           => __( 'The Google Cloud project has no active billing account. Google requires one for the Places API, even within the free monthly usage.', 'wille-reviews' ),
			'/SERVICE_DISABLED|has not been used|disabled/i' => __( 'The Places API (New) is not enabled in the key\'s Google Cloud project. Enable "Places API (New)" in the API library – the older "Places API" is not enough – and try again after a few minutes.', 'wille-reviews' ),
			'/API_KEY_INVALID|API key not valid|expired/i' => __( 'Google does not accept the API key. Copy it again from the Google Cloud Console (Credentials) without spaces and save the settings.', 'wille-reviews' ),
			'/HTTP 404|NOT_FOUND|Place ID|place_id/i'      => __( 'Google cannot find this Place ID. A Place ID usually starts with "ChIJ" – look it up with the Place ID Finder linked above; a Maps link or a CID does not work.', 'wille-reviews' ),
			'/HTTP 429|RESOURCE_EXHAUSTED|quota/i'         => __( 'The project\'s quota is used up. Check the quotas of the Places API (New) in the Google Cloud Console; the plugin tries again automatically.', 'wille-reviews' ),
			'/cURL error|timed out|resolve host/i'         => __( 'Your server could not reach Google. Ask your host whether outgoing connections to places.googleapis.com are allowed.', 'wille-reviews' ),
		);
		foreach ( $rules as $pattern => $hint ) {
			if ( preg_match( $pattern, (string) $message ) ) {
				return $hint;
			}
		}
		return '';
	}

	/**
	 * Language for the API: the setting, else the site language ("de_DE" → "de"; pure).
	 *
	 * @param string $setting Language setting ('' = site language).
	 * @param string $locale  WordPress locale.
	 * @return string
	 */
	public static function language_code( $setting, $locale ) {
		$code = '' !== $setting ? $setting : $locale;
		$code = strtolower( (string) preg_replace( '/[^A-Za-z_-]/', '', $code ) );
		$code = (string) strtok( str_replace( '_', '-', $code ), '-' );
		return '' !== $code ? $code : 'en';
	}

	/**
	 * Normalize a Place Details response (pure).
	 *
	 * @param array<string,mixed> $body     Decoded response.
	 * @param string              $place_id Place ID (for the "write a review" link).
	 * @param bool                $original Prefer the untranslated review text.
	 * @return array{name:string,rating:float,count:int,url:string,write_url:string,reviews:array<int,array<string,mixed>>,fetched_at:int}
	 */
	public static function normalize( array $body, $place_id, $original = true ) {
		$reviews = array();
		$raw     = isset( $body['reviews'] ) && is_array( $body['reviews'] ) ? $body['reviews'] : array();
		foreach ( $raw as $review ) {
			if ( ! is_array( $review ) ) {
				continue;
			}
			$text = isset( $review['text'] ) && is_array( $review['text'] ) ? $review['text'] : array();
			if ( $original && isset( $review['originalText']['text'] ) && '' !== (string) $review['originalText']['text'] ) {
				$text = $review['originalText'];
			}
			$author = isset( $review['authorAttribution'] ) && is_array( $review['authorAttribution'] ) ? $review['authorAttribution'] : array();
			$time   = isset( $review['publishTime'] ) ? strtotime( (string) $review['publishTime'] ) : false;

			$reviews[] = array(
				'id'            => substr( md5( (string) ( $review['name'] ?? wp_json_encode( $review ) ) ), 0, 12 ),
				'author'        => sanitize_text_field( (string) ( $author['displayName'] ?? '' ) ),
				'author_url'    => esc_url_raw( (string) ( $author['uri'] ?? '' ) ),
				'avatar_remote' => esc_url_raw( (string) ( $author['photoUri'] ?? '' ) ),
				'avatar'        => '',
				'rating'        => max( 0, min( 5, (int) round( (float) ( $review['rating'] ?? 0 ) ) ) ),
				'text'          => sanitize_textarea_field( (string) ( $text['text'] ?? '' ) ),
				'time'          => false !== $time ? $time : 0,
				'relative'      => sanitize_text_field( (string) ( $review['relativePublishTimeDescription'] ?? '' ) ),
			);
		}

		// Newest first (the API sorts by relevance).
		usort(
			$reviews,
			static function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		$name = isset( $body['displayName']['text'] ) ? (string) $body['displayName']['text'] : '';

		return array(
			'name'       => sanitize_text_field( $name ),
			'rating'     => round( max( 0.0, min( 5.0, (float) ( $body['rating'] ?? 0 ) ) ), 1 ),
			'count'      => max( 0, (int) ( $body['userRatingCount'] ?? 0 ) ),
			'url'        => esc_url_raw( (string) ( $body['googleMapsUri'] ?? '' ) ),
			'write_url'  => 'https://search.google.com/local/writereview?placeid=' . rawurlencode( (string) $place_id ),
			'reviews'    => $reviews,
			'fetched_at' => 0,
		);
	}

	/**
	 * Store the outcome of an API call.
	 *
	 * @param string $state   ok|error.
	 * @param string $message Details.
	 * @return void
	 */
	protected static function log( $state, $message ) {
		update_option(
			self::STATUS,
			array(
				'state'   => $state,
				'message' => $message,
				'time'    => time(),
			),
			false
		);
	}

	/**
	 * Outcome of the last API call.
	 *
	 * @return array{state:string,message:string,time:int}|null
	 */
	public static function status() {
		$status = get_option( self::STATUS );
		if ( ! is_array( $status ) || ! isset( $status['state'] ) ) {
			return null;
		}
		return array(
			'state'   => (string) $status['state'],
			'message' => (string) ( $status['message'] ?? '' ),
			'time'    => (int) ( $status['time'] ?? 0 ),
		);
	}

	/**
	 * Drop cache, backup and mirrored photos (the next view fetches fresh data).
	 *
	 * @return void
	 */
	public static function flush() {
		delete_transient( self::CACHE );
		delete_transient( self::LOCK );
		delete_option( self::BACKUP );
		WILLEREV_Avatars::cleanup( array() );
	}
}
