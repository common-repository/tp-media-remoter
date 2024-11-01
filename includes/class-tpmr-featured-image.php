<?php

class TPMR_Featured_Image {

	private $attachment;

	public function __construct( $attachment = null ) {

		if ( $attachment ) {
			
			if ( isset( $attachment['remotedata'] ) ) {
				unset( $attachment['remotedata'] );
			}
			$this->attachment = $attachment;
			
		}
	}
	
	/**
	 * Get attachment metadata
	 * @param int $attachmentId
	 * @return null|array
	 */
	public function get_attachment_metadata( $attachmentId ) {
		
		$metadata = wp_get_attachment_metadata( $attachmentId );
		
		if ( !empty( $metadata['isRemote'] )) {
			return $metadata;
		}
		
		return null;
	}

	/**
	 * Output HTML for the post thumbnail meta-box.
	 *
	 * @since 2.9.0
	 *
	 * @param int $thumbnail_id ID of the attachment used for thumbnail
	 * @param mixed $post The post ID or object associated with the thumbnail, defaults to global $post.
	 * @return string html
	 */
	public function post_thumbnail_html( $thumbnail_id, $post = null ) {

		$post = get_post( $post );

		$post_type_object = get_post_type_object( $post->post_type );
		$set_thumbnail_link = '<p class="hide-if-no-js"><a href="%s" id="set-post-thumbnail"%s class="thickbox">%s</a></p>';
		$upload_iframe_src = get_upload_iframe_src( 'image', $post->ID );

		$content = sprintf( $set_thumbnail_link, esc_url( $upload_iframe_src ), '', esc_html( $post_type_object->labels->set_featured_image ) );

		$src = '';

		$attachment = $this->attachment;

		if ( $attachment ) {

			$size = isset( $attachment['image_size'] ) ? $attachment['image_size'] : 'medium';

			$src = isset( $attachment['sizes'][$size]['url'] ) ? $attachment['sizes'][$size]['url'] : $attachment['url'];

			$thumbnail_html = $this->get_attachment_image( $size );

			if ( !empty( $thumbnail_html ) ) {
				$content = sprintf( $set_thumbnail_link, esc_url( $upload_iframe_src ), ' aria-describedby="set-post-thumbnail-desc"', $thumbnail_html );
				$content .= '<p class="hide-if-no-js howto" id="set-post-thumbnail-desc">' . __( 'Click the image to edit or update','tp-media-remoter' ) . '</p>';
				$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">' . esc_html( $post_type_object->labels->remove_featured_image ) . '</a></p>';
			}
		}

		$content .= '<input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="' . esc_attr( $thumbnail_id ) . '" />';

		return apply_filters( 'tpmr_post_thumbnail_html', $content, $post->ID, $attachment );
	}

	/**
	 * Get an HTML img element representing an image attachment
	 *
	 * While `$size` will accept an array, it is better to register a size with
	 * add_image_size() so that a cropped version is generated. It's much more
	 * efficient than having to find the closest-sized image and then having the
	 * browser scale down the image.
	 *
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|array $size          Optional. Image size. Accepts any valid image size, or an array of width
	 *                                    and height values in pixels (in that order). Default 'thumbnail'.
	 * @param bool         $icon          Optional. Whether the image should be treated as an icon. Default false.
	 * @param string|array $attr          Optional. Attributes for the image markup. Default empty.
	 * @return string HTML img element or empty string on failure.
	 */
	public function get_attachment_image( $size = 'thumbnail', $icon = false, $attr = '' ) {

		$html = '';

		$image = false;

		$attachment = $this->attachment;

		if ( isset( $attachment['sizes'][$size] ) ) {
			$image = $attachment['sizes'][$size];
		}

		if ( $image ) {

			$hwstring = image_hwstring( $image['width'], $image['height'] );
			$size_class = $size;
			if ( is_array( $size_class ) ) {
				$size_class = join( 'x', $size_class );
			}

			$default_attr = array(
				'src' => $image['url'],
				'class' => "attachment-$size_class size-$size_class",
				'alt' => '',
			);

			$attr = wp_parse_args( $attr, $default_attr );

			/**
			 * Filters the list of attachment image attributes.
			 *
			 * @param array        $attr       Attributes for the image markup.
			 * @param WP_Post      $attachment Image attachment post.
			 * @param string|array $size       Requested size. Image size or array of width and height values
			 *                                 (in that order). Default 'thumbnail'.
			 */
			$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );
			$attr = array_map( 'esc_attr', $attr );
			$html = rtrim( "<img $hwstring" );
			foreach ( $attr as $name => $value ) {
				$html .= " $name=" . '"' . $value . '"';
			}
			$html .= ' />';
		}

		return $html;
	}

	/*
	 * @return ID if image is already in DB, 0 otherwise
	 */
	protected function getIDfromGUID( $guid ) {
		global $wpdb;
		$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) );
		if ( empty( $attachment_id ) ) {
			$attachment_id = 0;
		}
		return $attachment_id;
	}

	/**
	 * Save attachment and save post thumbnail
	 */
	public function save( $post_id ) {

		$attachment = $this->attachment;

		$data = array(
			'guid' => $attachment['url'],
			'post_mime_type' => $attachment['mime'],
			'post_title' => $attachment['title'],
			'post_content' => '',
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'meta_input' => array( '_is_tpmr' => true )
		);

		//Returns ID = 0 if not already in DB
		//Reuse attachment with same guid (url)
		$data['ID'] = $this->getIDfromGUID( $data['guid'] );

		//First Insert as attachment type to proceed through WP attachments filters
		$attachment_id = wp_insert_post( $data );

		//Set Remote media metadata
		wp_update_attachment_metadata( $attachment_id, $attachment );

		/**
		 * Assign this remote attachment to post if post Id is set
		 */
		if ( !empty( $post_id ) ) {

			set_post_thumbnail( $post_id, $attachment_id );

			$updateData = array(
				'ID' => $attachment_id,
				'post_parent' => $post_id,
			);

			wp_update_post( $updateData );
		}

		return $attachment_id;
	}

}
