<?php
/**
 * OpenWeatherAPI class
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge\API;

use WebNorthCodeChallenge\Interfaces\IAPI;
use Exception;

/**
 * Handles interaction with the OpenWeather API.
 */
class OpenWeatherAPI implements IAPI {
	private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$options       = get_option( 'wncc_weather_settings' );
		$this->api_key = $options['api_key'] ?? '';
	}

	/**
	 * Get weather data from remote server or cache.
	 *
	 * @param float  $latitude  Latitude of the location.
	 * @param float  $longitude Longitude of the location.
	 * @param string $units     Units of measurement (default: 'metric').
	 *
	 * @return array|false Sanitized weather data array or false on failure.
	 */
	public function get_weather( float $latitude, float $longitude, string $units = 'metric' ): array|false {
		// Prepare query parameters.
		$query_args = array(
			'lat'   => $latitude,
			'lon'   => $longitude,
			'units' => $units,
			'appid' => $this->api_key,
		);

		// Fetch from API.
		try {
			$response = wp_remote_get( add_query_arg( $query_args, self::API_URL ), array( 'timeout' => 15 ) );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) { // Yoda condition applied.
				return false;
			}

			$body      = wp_remote_retrieve_body( $response );
			$result    = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
			$sanitized = $this->sanitize_data( $result );

			return $sanitized;
		} catch ( \JsonException | Exception $e ) {
			// Optional: log the error here.
			return false;
		}
	}

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed Sanitized data.
	 */
	private function sanitize_data( $data ) {
		if ( is_array( $data ) ) {
			$filtered = array();
			foreach ( $data as $key => $value ) {
				$sanitized_key              = is_string( $key ) ? $this->sanitize_data( $key ) : $key;
				$filtered[ $sanitized_key ] = $this->sanitize_data( $value );
			}
			return $filtered;
		}

		if ( is_string( $data ) ) {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		return $data;
	}
}
