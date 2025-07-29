<?php
/**
 * PHPUnit bootstrap file for WordPress plugin testing
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Initialize Brain\Monkey
use Brain\Monkey;
Monkey\setUp();

register_shutdown_function(
	function () {
		// Only call tearDown/close at very end.
		Monkey\tearDown();
		if ( class_exists( '\Mockery' ) ) {
			\Mockery::close();
		}
	}
);

// WP_Error stub (only once!)
if ( ! class_exists( '\WP_Error' ) ) {
	class WP_Error {
		private $code;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code;
		}
		public function get_error_code() {
			return $this->code;
		}
	}
}

// WP_REST_Request stub
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private array $params = array();

		public function __construct( $method = '', $route = '' ) {
			// no-op constructor for stub
		}

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		// optional: to make set_params(['key' => 'value']) usable
		public function set_params( array $params ): void {
			$this->params = $params;
		}

		public function get_header( string $key ) {
			return $this->headers[ $key ] ?? null;
		}

		public function set_header( string $key, $value ): void {
			$this->headers[ $key ] = $value;
		}

		public function set_headers( array $headers ): void {
			$this->headers = $headers;
		}
	}
}


// WP_REST_Server stub
if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE   = 'GET';
		const EDITABLE   = 'POST';
		const DELETABLE  = 'DELETE';
		const CREATABLE  = 'POST';
		const ALLMETHODS = 'GET,POST,PUT,DELETE,PATCH';
	}
}

// WP_REST_Response stub
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data;
		public function __construct( $data = null ) {
			$this->data = $data; }
		public function get_data() {
			return $this->data; }
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		/**
		 * Optional static instance for test purposes.
		 *
		 * @var WP_Query|null
		 */
		public static ?WP_Query $test_instance = null;

		/** @var int Total found posts */
		public int $found_posts = 0;

		/** @var int Maximum number of pages */
		public int $max_num_pages = 0;

		/** @var array The posts retrieved by the query */
		public array $posts = array();

		/** @var int Internal pointer for have_posts */
		private int $current_post_index = 0;

		/**
		 * Constructor optionally clones static test instance properties for stubbing.
		 *
		 * @param array $args Query arguments (ignored in stub)
		 */
		public function __construct( array $args = array() ) {
			// Only clone test_instance properties if set for isolation
			if ( self::$test_instance instanceof self ) {
				foreach ( get_object_vars( self::$test_instance ) as $property => $value ) {
					$this->$property = $value;
				}
			}
			// Reset the pointer on new instance
			$this->current_post_index = 0;
		}

		/**
		 * Simulates WordPress have_posts() loop method.
		 * Returns true if there are remaining posts in $posts array.
		 *
		 * @return bool
		 */
		public function have_posts(): bool {
			return $this->current_post_index < count( $this->posts );
		}

		/**
		 * Advances the internal pointer to the next post.
		 */
		public function the_post(): void {
			++$this->current_post_index;
		}

		/**
		 * Returns the current post object.
		 *
		 * @return object|null
		 */
		public function get_post() {
			return $this->posts[ $this->current_post_index ] ?? null;
		}

		/**
		 * Rewind the posts loop (useful for rerunning have_posts).
		 */
		public function rewind_posts(): void {
			$this->current_post_index = 0;
		}

		/**
		 * For compatibility with core: Return posts property.
		 */
		public function get_posts(): array {
			return $this->posts;
		}
	}
}


// Add this block:
if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public function prepare( $query, ...$args ) {
			return $query; }
		public function get_col( $query ) {
			return array(); }
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID;
		public string $post_type;

		public function __construct( int $ID, string $post_type = '' ) {
			$this->ID        = $ID;
			$this->post_type = $post_type;
		}
	}
}

// Get plugin path.
define( 'WEBNORTH_CODE_CHALLENGE_PLUGIN_PATH', dirname( __DIR__ ) . '/' );

// Plugin file.
define( 'WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE', dirname( __DIR__ ) . '/../plugin.php' );

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
