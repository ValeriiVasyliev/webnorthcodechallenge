<?php
/**
 * API cache helper class
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge\API;

/**
 * Class to handle storing, retrieving, and clearing cached API data.
 */
class APICacheHelper {

	/**
	 * Cache expiration in seconds (default: 1 day).
	 */
	private const TRANSIENT_EXPIRATION = 86400;

	/**
	 * Retrieve cached data by transient key.
	 *
	 * @param string $transient_key Cache key.
	 * @return array Cached data or empty array if not found or invalid.
	 */
	public static function get( string $transient_key ): array {
		$cached = get_transient( $transient_key );
		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Store data in cache using a transient key.
	 *
	 * @param string $transient_key Cache key.
	 * @param array  $data          Data to cache.
	 * @param int    $expiration    Optional. Expiration time in seconds. Default is class constant.
	 * @return bool True if the transient was set, false otherwise.
	 */
	public static function set( string $transient_key, array $data, int $expiration = self::TRANSIENT_EXPIRATION ): bool {
		return set_transient( $transient_key, $data, $expiration );
	}

	/**
	 * Clear cached data by transient key.
	 *
	 * @param string $transient_key Cache key.
	 * @return bool True if the transient was deleted.
	 */
	public static function clear( string $transient_key ): bool {
		return delete_transient( $transient_key );
	}
}
