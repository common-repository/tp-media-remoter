<?php

class TPMR_Ajax {

	/**
	 * Hook in ajax handlers.
	 * 
	 * @return void
	 */
	public static function init() {

		/**
		 * Register ajax event
		 */
		self::add_ajax_events( array(
			'remote_attachments' => false,
			'attachment_to_editor' => false,
			'upload_attachments' => false,
			'featured_html' => false,
			'register_token' => false,
			'validate_token' => false,
		) );
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 * 
	 * @param array $ajax_events
	 * @return void
	 */
	public static function add_ajax_events( $ajax_events ) {

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_tpmr_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_tpmr_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Get attachments from service
	 * @since 1.0.0
	 */
	public static function remote_attachments() {

		$media = new TPMR_Media();
		$attachments = $media->get_attachments();

		$return = array();

		$return['data'] = array();

		try {
			if ( is_null( $attachments ) ) {
				$return['success'] = false;
				$return['statuscode'] = 0;
				$return['msg'] = __( 'Account service not found', 'tp-media-remoter' );
				wp_send_json( $return );
			}
			$return['data'] = $attachments;
		} catch ( ClientErrorResponseException $e ) {
			$return['success'] = false;
			$return['statuscode'] = $e->getResponse()->getStatusCode();
			$return['msg'] = $e->getResponse()->getMessage();
			wp_send_json( $return );
		} catch ( CurlException $e ) {
			$return['success'] = false;
			$return['statuscode'] = $e->getErrorNo();
			$return['msg'] = $e->getError();
			wp_send_json( $return );
		} catch ( \Exception $e ) {
			$return['success'] = false;
			$return['statuscode'] = $e->getCode();
			$return['msg'] = $e->getMessage();
			wp_send_json( $return );
		}

		wp_send_json_success( $return['data'] );
	}

	/**
	 * Ajax send attachment to editor
	 * @since 1.0.0
	 */
	public static function attachment_to_editor() {

		$attachment = wp_unslash( $_POST['attachment'] );

		$html = '';

		$media = new TPMR_Media();

		/**
		 * Embed for video
		 */
		if ( is_null( $attachment ) ) {

			if ( empty( $attachment['url'] ) ) {
				wp_send_json_error();
			}

			$html = '[embed]' . $attachment['url'] . '[/embed]';
			wp_send_json_success( $html );
		}

		unset( $attachment['remotedata'] );

		$html = $media->toEditorHtml( $attachment );

		wp_send_json_success( $html );
	}

	/**
	 * Ajax upload media
	 * @since 1.0.0
	 */
	public static function upload_attachments() {

		$title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$desc = isset( $_POST['desc'] ) ? sanitize_text_field( $_POST['desc'] ) : '';
		
		$media = new TPMR_Media();
		$attachment = $media->upload( $title, $desc, $_FILES['files']['tmp_name'], $_FILES['files']['type'] );
		if ( is_array( $attachment ) ) {
			$attachment['toEditor'] = $media->toEditorHtml( $attachment );
		}
		
		wp_send_json_success( $attachment );
	}

	/**
	 * Set featured image
	 * @since 1.0.0
	 */
	public static function featured_html() {

		$post_id = absint( $_POST['post_id'] );
		$attachment = wp_unslash( $_POST['attachment'] );
		$attachment['image_size'] = $_POST['image_size'];

		/**
		 * Save new data
		 */
		$featured = new TPMR_Featured_Image( $attachment );
		$thumbnail_id = $featured->save( $post_id );
		$return = $featured->post_thumbnail_html( $thumbnail_id, $post_id );

		wp_send_json_success( $return );
	}

	/**
	 * Register token
	 * @since 1.0.0
	 */
	public static function register_token() {

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		$errors = array();

		if ( !wp_verify_nonce( $nonce, 'tpmr_register_token' ) ) {
			$errors[] = __( 'Sorry, your security key did not verify.', 'tp-media-remoter' );
		}

		if ( !is_email( $email ) ) {
			$errors[] = __( 'Please, enter a valid email.', 'tp-media-remoter' );
		}

		if ( !empty( $errors ) ) {
			wp_send_json( array(
				'status' => 400,
				'errors' => $errors
			) );
		}

		$user = new TPMR_User( $email );
		$response = $user->register();
		$response = json_decode( $response );

		if ( !$response->success ) {
			wp_send_json( array(
				'status' => 202,
				'data' => array(),
				'errors' => array( $response->data )
			) );
		}

		$user->set_validate_key();

		wp_send_json( array(
			'status' => 200,
			'data' => array(
				'email' => $email,
				'msg' => $response->data
			)
		) );
	}

	/**
	 * Validate token
	 */
	public static function validate_token() {

		$errors = array();

		if ( empty( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'tpmr_validate_token' ) ) {
			$errors[] = __( 'Sorry, your security key did not verify.', 'tp-media-remoter' );
		}

		if ( empty( $_POST['token'] ) ) {
			$errors[] = __( 'Your product key can not be empty.', 'tp-media-remoter' );
		}

		if ( empty( $_POST['email'] ) || !is_email( $_POST['email'] ) ) {
			$errors[] = __( 'Email is not validate.', 'tp-media-remoter' );
		}

		if ( !empty( $errors ) ) {
			wp_send_json( array(
				'status' => 400,
				'errors' => $errors
			) );
		}

		$email = sanitize_email( $_POST['email'] );
		$token = sanitize_text_field( $_POST['token'] );
		
		$user = new TPMR_User( $email, $token );
		$response = $user->validate();
		$response = json_decode( $response );
		
		if ( !$response->success ) {
			update_option( 'tpmr_key', '' );
			wp_send_json( array(
				'status' => 202,
				'data' => array(),
				'errors' => array( $response->data )
			) );
		}
		
		$user->set_validate_key();
		
		wp_send_json( array(
			'status' => 200,
			'data' => array(
				'email' => $email,
				'token' => $token,
				'msg' => $response->data
			)
		) );
		
	}

}

TPMR_Ajax::init();
