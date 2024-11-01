<?php

/**
 * User Service
 */
class TPMR_User {

	/**
	 * @var string $token Product Key
	 */
	protected $token;

	/**
	 * @var string $email Email to register a product key
	 */
	protected $email;

	/**
	 * @var string $user_api Server Api url
	 */
	protected $user_api;

	public function __construct( $email = '', $token = '' ) {

		$this->user_api = 'http://api.themespond.com/api';

		if ( !empty( $email ) ) {
			$this->email = $email;
			$this->token = strtolower( $token);
		} else {
			$this->email = sanitize_email( get_option( 'tpmr_email' ));
			$this->token = strtolower(get_option( 'tpmr_key' ));
		}
	}

	public function get_token() {
		return $this->token;
	}

	public function get_email() {
		return $this->email;
	}

	/**
	 * Register a token
	 * @param string $email
	 * @return array
	 */
	public function register() {

		$response = wp_remote_post( $this->user_api . '/register', array( 'body' => array(
				'action' => 'register',
				'service' => 'tp_media_remoter',
				'email' => $this->email
			) ) );

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Validate a token
	 * @param string $email
	 * @param string $token
	 * @return array
	 */
	public function validate() {
		
		$response = wp_remote_post( $this->user_api . '/validate', array( 'body' => array(
				'service' => 'tp_media_remoter',
				'email' => $this->email,
				'token' => $this->token
			) ) );
		
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Check is validate online
	 * @return bool
	 */
	public function is_validate() {

		if ( !empty( $this->email ) && !empty( $this->token ) ) {

			$salt = md5( $this->email . $this->token );

			if ( $salt == get_option( 'tpmr_salt' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Save validate to a key
	 * @return void
	 */
	public function set_validate_key() {
		$salt = md5( $this->email . $this->token );
		update_option( 'tpmr_salt', $salt );
		update_option( 'tpmr_key', $this->token );
		update_option( 'tpmr_email', $this->email );
	}

}
