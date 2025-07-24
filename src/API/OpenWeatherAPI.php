<?php
/**
 * PokeAPI class to interact with the PokéAPI.
 *
 * @package WebNorthCodeChallenge
 */

namespace WebNorthCodeChallenge\API;

use Exception;
use WebNorthCodeChallenge\Interfaces\IAPI;

/**
 * Class OpenWeatherAPI
 *
 * Handles interaction with the OpenWeather API.
 */
class OpenWeatherAPI implements IAPI {

	private const TRANSIENT_EXPIRATION = 600;

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data to sanitize.
	 * @return mixed
	 */
	public function sanitize_data( $data ) {
		if ( is_array( $data ) ) {
			$filtered = array();

			foreach ( $data as $key => $value ) {
				$sanitized_key              = is_string( $key ) ? $this->sanitize_data( $key ) : $key;
				$filtered[ $sanitized_key ] = $this->sanitize_data( $value );
			}

			return $filtered;
		}

		// Only sanitize strings.
		if ( is_string( $data ) ) {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		return $data;
	}

	/**
	 * Fetch and cache data from a given API URL.
	 *
	 * @param string $url           API endpoint.
	 * @param string $transient_key Cache key.
	 * @param bool   $force         Force refresh from API.
	 * @return array
	 */
	private function fetch_and_cache( string $url, string $transient_key, bool $force = false ): array {
		$cached = get_transient( $transient_key );

		if ( false === $cached || true === $force ) {
			try {
				$response = wp_remote_get( $url );

				if (
					! is_wp_error( $response ) &&
					200 === wp_remote_retrieve_response_code( $response )
				) {
					$data = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );

					if ( is_array( $data ) ) {
						$data = $this->sanitize_data( $data );
						set_transient( $transient_key, $data, self::TRANSIENT_EXPIRATION );
						return $data;
					}
				}
			} catch ( Exception $ex ) {
				// Log the error.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
                    // phpcs:disable WordPress.PHP.DevelopmentFunctions
					error_log( 'PokéAPI error: ' . $ex->getMessage() );
                    // phpcs:enable
				}
			}

			// Return empty array if request failed or data was invalid.
			return array();
		}

		// Ensure cached value is array.
		return is_array( $cached ) ? $cached : array();
	}
}
