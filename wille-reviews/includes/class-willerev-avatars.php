<?php
/**
 * Local copies of the reviewers' profile photos (uploads/wille-reviews/): the front end never hotlinks
 * Google's image servers, so visitors' browsers do not contact Google.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Avatar mirror.
 */
class WILLEREV_Avatars {

	/**
	 * Largest accepted photo.
	 */
	const MAX_BYTES = 1048576;

	/**
	 * Folder and URL of the mirrored photos.
	 *
	 * @return array{dir:string,url:string}
	 */
	public static function paths() {
		$uploads = wp_upload_dir( null, false );
		return array(
			'dir' => trailingslashit( $uploads['basedir'] ) . 'wille-reviews',
			'url' => trailingslashit( $uploads['baseurl'] ) . 'wille-reviews',
		);
	}

	/**
	 * Image type from the file's magic bytes – the Content-Type header is not trusted (pure).
	 *
	 * @param string $bytes File content.
	 * @return string jpg|png|gif|webp, or '' for anything else.
	 */
	public static function image_type( $bytes ) {
		if ( 0 === strncmp( $bytes, "\xFF\xD8\xFF", 3 ) ) {
			return 'jpg';
		}
		if ( 0 === strncmp( $bytes, "\x89PNG\r\n\x1A\n", 8 ) ) {
			return 'png';
		}
		if ( 0 === strncmp( $bytes, 'GIF87a', 6 ) || 0 === strncmp( $bytes, 'GIF89a', 6 ) ) {
			return 'gif';
		}
		if ( 0 === strncmp( $bytes, 'RIFF', 4 ) && 'WEBP' === substr( $bytes, 8, 4 ) ) {
			return 'webp';
		}
		return '';
	}

	/**
	 * Download a profile photo once and return its local URL ('' on failure → initials are shown).
	 *
	 * @param string $remote_url Photo URL from the Places API.
	 * @return string
	 */
	public static function mirror( $remote_url ) {
		if ( '' === $remote_url || 0 !== strpos( $remote_url, 'https://' ) ) {
			return '';
		}
		$paths = self::paths();
		$hash  = md5( $remote_url );
		foreach ( array( 'jpg', 'png', 'gif', 'webp' ) as $ext ) {
			if ( file_exists( $paths['dir'] . '/' . $hash . '.' . $ext ) ) {
				return $paths['url'] . '/' . $hash . '.' . $ext;
			}
		}

		$response = wp_safe_remote_get(
			$remote_url,
			array(
				'timeout'             => 8,
				'limit_response_size' => self::MAX_BYTES,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}
		$body = (string) wp_remote_retrieve_body( $response );
		$ext  = self::image_type( $body );
		if ( '' === $ext || strlen( $body ) >= self::MAX_BYTES ) {
			return '';
		}

		$filesystem = self::filesystem();
		if ( ! $filesystem || ! wp_mkdir_p( $paths['dir'] ) ) {
			return '';
		}
		$file = $paths['dir'] . '/' . $hash . '.' . $ext;
		if ( ! $filesystem->put_contents( $file, $body, FS_CHMOD_FILE ) ) {
			return '';
		}
		return $paths['url'] . '/' . $hash . '.' . $ext;
	}

	/**
	 * Delete mirrored photos that no current review uses.
	 *
	 * @param string[] $keep_urls Local URLs still in use.
	 * @return void
	 */
	public static function cleanup( $keep_urls ) {
		$paths = self::paths();
		$keep  = array();
		foreach ( $keep_urls as $url ) {
			if ( '' !== (string) $url ) {
				$keep[] = basename( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
			}
		}
		// No GLOB_BRACE: it does not exist on every platform (musl/Alpine).
		$files = glob( $paths['dir'] . '/*' );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			if ( ! preg_match( '/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/', basename( $file ) ) ) {
				continue;
			}
			if ( is_file( $file ) && ! in_array( basename( $file ), $keep, true ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * WordPress filesystem abstraction (direct access only – cron runs without credentials).
	 *
	 * @return WP_Filesystem_Base|null
	 */
	protected static function filesystem() {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base && ! WP_Filesystem() ) {
			return null;
		}
		return $wp_filesystem instanceof WP_Filesystem_Base ? $wp_filesystem : null;
	}
}
