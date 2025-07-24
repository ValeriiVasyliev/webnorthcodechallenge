<?php
/**
 * Interface for general API interaction.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge\Interfaces;

interface IAPI {
	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed Sanitized data.
	 */
	public function sanitize_data( $data );
}
