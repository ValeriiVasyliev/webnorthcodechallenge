<?php
/**
 * OpenWeatherAPI class
 *
 * @package webnorthcodechallenge
 */
namespace WebNorthCodeChallenge\API;

use WebNorthCodeChallenge\Interfaces\IAPI;

/**
 * Handles interaction with the OpenWeather API.
 */
class OpenWeatherAPI implements IAPI {

	const API_URL = 'https://api.openweathermap.org/data/2.5/';

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data.
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

		if ( is_string( $data ) ) {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		return $data;
	}

	/**
	 * Wrapper to fetch data using the shared caching helper.
	 *
	 * @param string $url API endpoint.
	 * @param string $transient_key Cache key.
	 * @param bool   $force Force refresh.
	 * @return array
	 */
	public function get_weather_data( string $url, string $transient_key, bool $force = false ): array {
		return APICacheHelper::fetch_and_cache( $url, $transient_key, array( $this, 'sanitize_data' ), $force );
	}
}
