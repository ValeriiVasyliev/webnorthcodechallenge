<?php
/**
 * API cache helper class
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge\API;

use Exception;

/**
 * Class to handle API data fetching and caching.
 */
class APICacheHelper {

	private const TRANSIENT_EXPIRATION = 600;

	/**
	 * Fetch and cache data from a given API URL.
	 *
	 * @param string   $url            API endpoint.
	 * @param string   $transient_key  Cache key.
	 * @param callable $sanitizer      Optional callback to sanitize data.
	 * @param bool     $force_refresh  Force refresh from API.
	 * @return array
	 */
	public static function fetch_and_cache(
		string $url,
		string $transient_key,
		callable $sanitizer = null,
		bool $force_refresh = false
	): array {
		$cached = get_transient( $transient_key );

		if ( false === $cached || $force_refresh ) {
			try {
				$response = wp_remote_get( $url );
				if (
					! is_wp_error( $response ) &&
					200 === wp_remote_retrieve_response_code( $response )
				) {
					$data = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );

					if ( is_array( $data ) ) {
						if ( $sanitizer ) {
							$data = call_user_func( $sanitizer, $data );
						}

						set_transient( $transient_key, $data, self::TRANSIENT_EXPIRATION );
						return $data;
					}
				}
			} catch ( Exception $ex ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'API cache error: ' . $ex->getMessage() );
				}
			}
			return array();
		}

		return is_array( $cached ) ? $cached : array();
	}
}
