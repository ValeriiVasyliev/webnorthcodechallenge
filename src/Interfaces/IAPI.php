<?php
/**
 * Interface for general API interaction.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge\Interfaces;

interface IAPI {

	/**
	 * Get weather data from remote server or cache.
	 *
	 * @param float  $latitude  Latitude of the location.
	 * @param float  $longitude Longitude of the location.
	 * @param string $units     Units of measurement (default: 'metric').
	 *
	 * @return array|false Sanitized weather data array or false on failure.
	 */
	public function get_weather( float $latitude, float $longitude, string $units = 'metric' ): array|false;
}
