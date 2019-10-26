<?php
/**
 * Cross_Check_AJAX forked from WP_AJAX
 *
 * A simple class for creating active
 * record, eloquent-esque models of WordPress Posts.
 *
 * WP_AJAX @author AnthonyBudd <anthonybudd94@gmail.com>
 */
abstract class Cross_Check_AJAX {

	protected $action;
	public $request;
	public $wp;
	public $user;


	abstract protected function run();

	public function __construct() {
		global $wp;
		$this->wp = $wp;
		$this->request = $_REQUEST;

		if ( $this->is_logged_in() ) {
			$this->user = wp_get_current_user();
		}
	}

	public static function boot() {
		$class = self::get_class_name();
		$action = new $class();
		$action->run();
		die();
	}

	public static function listen( $public = true ) {
		$actionName = self::get_action_name();
		$className = self::get_class_name();
		add_action( "wp_ajax_{$actionName}", [ $className, 'boot' ] );

		if ( $public ) {
			add_action( "wp_ajax_nopriv_{$actionName}", [ $className, 'boot' ] );
		}
	}


	// -----------------------------------------------------
	// UTILITY METHODS
	// -----------------------------------------------------
	public static function get_class_name() {
		return get_called_class();
	}

	public static function ajax_form_url() {
		return admin_url( '/admin-ajax.php' );
	}

	public static function get_action_name() {
		// pbrocks renamed since self::get_action_name() otherwise undefined
		// public static function action() {
		$class = self::get_class_name();
		$reflection = new ReflectionClass( $class );
		$action = $reflection->newInstanceWithoutConstructor();
		if ( ! isset( $action->action ) ) {
			throw new Exception( 'Public property $action not provied' );
		}

		return $action->action;
	}

	// -----------------------------------------------------
	// JSONResponse
	// -----------------------------------------------------
	public function return_back_json() {
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
			die();
		}

		return false;
	}

	public function return_redirect( $url, $params = array() ) {
		$url .= '?' . http_build_query( $params );
		ob_clean();
		header( 'Location: ' . $url );
		die();
	}

	public function return_json( $data ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $data );
		die;
	}

	// -----------------------------------------------------
	// Helpers
	// -----------------------------------------------------
	public static function the_ajax_url() {
		?>
			<script type="text/javascript">
				var ajaxurl = '<?php echo admin_url( '/admin-ajax.php' ); ?>';
			</script>
		<?php
	}

	public static function wp_head_ajax_url() {
		add_action( 'wp_head', [ 'Cross_Check_AJAX', 'the_ajax_url' ] );
	}

	public static function url( $params = array() ) {
		$params = http_build_query(
			array_merge(
				array(
					'action' => ( new static() )->action,
				), $params
			)
		);

		return admin_url( '/admin-ajax.php' ) . '?' . $params;
	}

	public function is_logged_in() {
		return is_user_logged_in();
	}

	public function has( $key ) {
		if ( isset( $this->request[ $key ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * [get description]
	 *
	 * @param  string $key     [description]
	 * @param  string $default [description]
	 * @return strin
	 */
	public function get( $key, $default = null, $stripslashes = true ) {
		if ( $this->has( $key ) ) {
			if ( $stripslashes ) {
				return stripslashes( $this->request[ $key ] );
			}
			return $this->request[ $key ];
		}
		return $default;
	}

	/**
	 * @param string|array $type The type of request you want to
	 *  check. If an array this method will return true if the
	 *  request matches any type.
	 *
	 * @return [type]              [description]
	 */
	public function request_type( $request_type = null ) {
		if ( ! is_null( $request_type ) ) {

			if ( is_array( $request_type ) ) {
				return in_array( $_SERVER['REQUEST_METHOD'], array_map( 'strtoupper', $request_type ) );
			}

			return ( $_SERVER['REQUEST_METHOD'] === strtoupper( $request_type ) );
		}

		return $_SERVER['REQUEST_METHOD'];
	}
}
