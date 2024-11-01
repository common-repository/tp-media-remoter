<?php

/**
 * Media remoter Class
 * @since 1.0.0
 */
if ( !class_exists( 'TPMR_Media' ) ) {

	class TPMR_Media extends TPMR_User {

		private $remote_api = '';

		public function __construct() {

			parent::__construct();
			
			$this->remote_api = 'http://api.themespond.com/api/v1/remote/imgdrive';
		}

		/**
		 * Get image src
		 * @param array $attachment
		 * @param string $size
		 * @return array
		 */
		public function get_image_src( $attachment, $size = 'medium' ) {
			if ( isset( $attachment['sizes'][$size] ) ) {
				return $attachment['sizes'][$size];
			}

			return array();
		}

		/**
		 * Get image url
		 * @param array $attachment
		 * @param string $size
		 * @return string
		 */
		public function get_image_url( $attachment, $size = 'medium' ) {
			if ( isset( $attachment['sizes'][$size] ) ) {
				return $attachment['sizes'][$size]['url'];
			}

			return '';
		}

		/**
		 * Gets an img tag for an image attachment, scaling it down if requested.
		 * @see get_image_tag()
		 * @since 1.0.0
		 * @return string HTML IMG element for given image attachment
		 */
		public function get_image_tag( $id, $alt, $title, $align, $img_src, $width, $height, $size = 'medium' ) {

			$hwstring = image_hwstring( $width, $height );

			$title = $title ? 'title="' . esc_attr( $title ) . '" ' : '';

			$class = 'align' . esc_attr( $align ) . ' size-' . esc_attr( $size ) . '  tpmr-attachment wp-image-' . esc_attr( $id );

			/**
			 * Filters the value of the attachment's image tag class attribute.
			 */
			$class = apply_filters( 'get_image_tag_class', $class, $id, $align, $size );

			$html = '<img src="' . esc_attr( $img_src ) . '" alt="' . esc_attr( $alt ) . '" ' . $title . $hwstring . 'class="' . $class . '" />';
			wp_remote_post( $html );
			/**
			 * Filters the HTML content for the image tag.
			 */
			return apply_filters( 'get_image_tag', $html, $id, $alt, $title, $align, $size );
		}

		/**
		 * Retrieves the image HTML to send to the editor.
		 * @see get_image_send_to_editor()
		 * @return string The HTML output to insert into the editor.
		 */
		public function get_image_send_to_editor( $attachment ) {

			$id = $attachment['id'];

			$url = $attachment['url'];

			$align = isset( $attachment['align'] ) ? $attachment['align'] : 'none';
			$size = isset( $attachment['image_size'] ) ? $attachment['image_size'] : 'medium';
			$alt = isset( $attachment['image_alt'] ) ? $attachment['image_alt'] : '';
			$rel = isset( $attachment['rel'] ) ? $attachment['rel'] : false;
			// No whitespace-only captions.
			$caption = isset( $attachment['post_excerpt'] ) ? $attachment['post_excerpt'] : '';
			if ( '' === trim( $caption ) ) {
				$caption = '';
			}

			$sizes = array(
				'width' => '',
				'height' => '',
				'file' => $url
			);

			if ( isset( $attachment['sizes'][$size] ) ) {
				$sizes = $attachment['sizes'][$size];
			}

			$width = $sizes['width'];

			$height = $sizes['height'];

			$imgsrc = $sizes['file'];

			$title = ''; // We no longer insert title tags into <img> tags, as they are redundant.

			$html = $this->get_image_tag( $id, $alt, $title, $align, $imgsrc, $width, $height, $size );

			if ( $rel ) {
				if ( is_string( $rel ) ) {
					$rel = ' rel="' . esc_attr( $rel ) . '"';
				} else {
					$rel = ' rel="attachment wp-att-' . $id . '"';
				}
			} else {
				$rel = '';
			}

			if ( $url ) {
				$html = '<a href="' . esc_url( $url ) . '"' . $rel . '>' . $html . '</a>';
			}


			return apply_filters( 'image_send_to_editor', $html, $id, $caption, $title, $align, $url, $size, $alt );
		}

		/**
		 * Scale down the default size of an image.
		 * This is so that the image is a better fit for the editor and theme.
		 * @since 1.0.0
		 * 
		 * @param array $media
		 * @param string $size
		 */
		public function getDownSize( $media, $size ) {

			$file = isset( $media['link'] ) ? $media['link'] : $media['url'];

			$width = $media['width'];

			$height = $media['height'];

			if ( $size == 'thumbnail' ) {
				//$file = str_replace( $media['id'], $media['id'] . 'b', $media['link'] );
				$file = $media['link'];
				$width = 160;
				$height = 160;
			} else if ( $size == 'medium' ) {
				//$file = str_replace( $media['id'], $media['id'] . 'm', $media['link'] );
				$file = $media['link'];
				$dimensions = wp_constrain_dimensions( $width, $height, 320, 320 );
				$width = $dimensions[0];
				$height = $dimensions[1];
			} else if ( $size == 'medium_large' ) {
				//$file = str_replace( $media['id'], $media['id'] . 'l', $media['link'] );
				$file = $media['link'];
				$dimensions = wp_constrain_dimensions( $width, $height, 640, 640 );
				$width = $dimensions[0];
				$height = $dimensions[1];
			} else if ( $size == 'large' ) {
				//$file = str_replace( $media['id'], $media['id'] . 'h', $media['link'] );
				$file = $media['link'];
				$dimensions = wp_constrain_dimensions( $width, $height, 1024, 1024 );
				$width = $dimensions[0];
				$height = $dimensions[1];
			}

			return array(
				'file' => $file,
				'url' => $file,
				'width' => $width,
				'height' => $height,
				'orientation' => $height > $width ? 'portrait' : 'landscape',
			);
		}

		/**
		 * Prepares a media object for JS, where it is expected
		 * to be JSON-encoded and fit into an Attachment model.
		 * @since 1.0.
		 *
		 * @return array Array of attachment details.
		 */
		public function toAttachment( $media ) {

			$mimes = explode( '/', $media['type'] );
			
			$type = $mimes[0];
			
			if(!isset($type[1])){
				$type = 'image';
			}

			$subtype = 'tpmr';

			//$icon = str_replace( $media['id'], $media['id'] . 'b', $media['link'] );
			$icon = $media['link'];
			
			$response = array(
				'id' => sanitize_text_field( $media['id'] ),
				'title' => $media['name'],
				'filename' => $media['name'],
				'url' => $media['link'],
				'link' => $media['link'],
				'alt' => $media['name'],
				'author' => $media['account_url'],
				'description' => trim( $media['description'] ),
				'caption' => '',
				'name' => isset( $media['name'] ) ? $media['name'] : '',
				'status' => 'inherit',
				'uploadedTo' => 0,
				'date' => strtotime( $media['datetime'] ) * 1000,
				'modified' => strtotime( $media['datetime'] ) * 1000,
				'menuOrder' => 0,
				'mime' => $media['type'],
				'type' => $type,
				'subtype' => $subtype,
				'icon' => $icon,
				'dateFormatted' => mysql2date( __( 'F j, Y', 'tp-media-remoter' ), $media['datetime'] ),
				'nonces' => array(
					'update' => false,
					'delete' => false,
					'edit' => false
				),
				'editLink' => false,
				'meta' => false,
				//Additional options
				'isRemote' => true,
				'remotetype' => 'image',
				'token' => $this->get_token(),
				'remotedata' => $media,
				'width' => $media['width'],
				'height' => $media['height']
			);

			if ( $media['size'] ) {
				$response['filesizeInBytes'] = $media['size'];
				$response['filesizeHumanReadable'] = size_format( $media['size'] );
			}

			$response['sizes'] = array(
				'thumbnail' => $this->getDownSize( $media, 'thumbnail' ),
				'medium' => $this->getDownSize( $media, 'medium' ),
				'medium_large' => $this->getDownSize( $media, 'medium_large' ),
				'large' => $this->getDownSize( $media, 'large' ),
				'full' => $this->getDownSize( $media, 'full' )
			);
			
			return $response;
		}
		
		/**
		 * Generates the HTML to send an attachment to the editor.
		 * @see wp_ajax_send_attachment_to_editor()
		 * @since 1.0.0
		 * @return string Html
		 */
		public function toEditorHtml( $attachment ) {

			$id = $attachment['id'];

			$attachment['image_size'] = 'medium_large'; //fix default;

			$url = empty( $attachment['url'] ) ? '' : $attachment['url'];
			
			$rel = '';

			remove_filter( 'media_send_to_editor', 'image_media_send_to_editor' );
			
			if ( 'image' === $attachment['type'] ) {
				$html = $this->get_image_send_to_editor( $attachment );
			} else if ( $attachment['url'] ) {
				$html = '[embed]' . $attachment['url'] . '[/embed]';
			} else {
				$html = isset( $attachment['post_title'] ) ? $attachment['post_title'] : '';
				$rel = $rel ? ' rel="attachment wp-att-' . $id . '"' : ''; // Hard-coded string, $id is already sanitized

				if ( !empty( $url ) ) {
					$html = '<a href="' . esc_url( $url ) . '"' . $rel . '>' . $html . '</a>';
				}
			}

			/** This filter is documented in wp-admin/includes/media.php */
			return apply_filters( 'media_send_to_editor', $html, $id, $attachment );
		}

		/**
		 * Get All attachments form server
		 * @since 1.0.0
		 * @return array Attachments
		 */
		public function get_attachments() {

			if ( !$this->is_validate() ) {
				throw new Exception( __( 'Email and product key are invalid.', 'tp-media-remoter' ) );
			}
			
			$reponse = wp_remote_get( $this->remote_api . '/images/'.$this->get_token(), array(
				'timeout' => 300
				 ) );
		
			$attachments = array();
			
			$medias = wp_remote_retrieve_body( $reponse );
		
			$medias = json_decode( $medias, true );
			
			if ( !empty( $medias['success'] ) ) {
				$medias = $medias['data'];
				foreach ( $medias as $i => $media ) {
					$attachments[$i] = $this->toAttachment( $media );
				}
			}
			
			return $attachments;
		}

		/**
		 * Submit a media file to server
		 * @since 1.0.0
		 * 
		 * @param string $title Image name
		 * @param string $desc	Image description
		 * @param array	CURLFile
		 * @return array Attachment format
		 */
		public function upload( $title, $desc, $file, $mime ) {

			if ( !$this->is_validate() ) {
				throw new Exception( __( 'Email and product key are invalid.', 'tp-media-remoter' ) );
			}

			$data = array(
				'headers' => array(
					'authentication' => $this->get_token(),
					'tpmr-title' => $title,
					'tpmr-desc' => $desc,
					'tpmr-mime' => $mime,
				),
				'body' => file_get_contents( $file ),
			);
			
			$response = wp_remote_post( $this->remote_api . '/images', $data );
	
			$response = wp_remote_retrieve_body( $response );
			
			$data = json_decode( $response, true );
			
			if ( $data['success'] ) {
				return $this->toAttachment( $data['data'] );
			}

			return 0;
		}

	}

}


