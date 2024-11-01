<?php

class TPMR_Hooks extends TPMR_User {

	public function __construct() {

		parent::__construct();

		add_filter( 'media_view_settings', array( $this, 'setting_accounts' ), 10, 2 );
		add_action( 'print_media_templates', array( $this, 'template_upload_imgur' ) );
		add_action( 'print_media_templates', array( $this, 'template_attachment_remoter' ) );
		add_filter( 'image_size_names_choose', array( $this, 'image_size_names_choose' ) );

		add_action( 'ajax_query_attachments_args', array( $this, 'modify_ajax_attachment_query_args' ) );
		add_action( 'admin_post_thumbnail_html', array( $this, 'admin_post_thumbnail_html' ), 10, 3 );
		add_filter( 'pre_get_posts', array( $this, 'modify_attachment_query' ) );
		add_filter( 'post_thumbnail_html', array( $this, 'post_thumbnail_html' ), 10, 5 );
	}

	public function image_size_names_choose( $sizes ) {
		$sizes = array_slice( $sizes, 0, 2, true ) +
				array( 'medium_large' => esc_attr__( 'Medium Large', 'tp-media-remoter' ) ) +
				array_slice( $sizes, 2, count( $sizes ) - 2, true );

		return $sizes;
	}

	public function setting_accounts( $settings, $post ) {
		
		if ( !$this->is_validate() ) {
			return $settings;
		}
		
		$settings['tpmr_accounts'] = array(
			array(
				'id' => $this->token,
				'type' => 'tpmr',
				'accounttitle' => __( 'Media Remoter', 'tp-media-remoter' ),
				'title' => __( 'Insert Media Remoter', 'tp-media-remoter' ),
				'filterable' => '',
				'filters' => array(),
				'remoteuploadable' => true,
				'uioptions' => array(),
				'featuredSelectable' => true,
				'featuredtitle' => __( 'Set from Media Remoter', 'tp-media-remoter' )
			)
		);

		/**
		 * Setting current featured image
		 */
		if ( !empty( $settings['post']['featuredImageId'] ) ) {

			$fetured = new TPMR_Featured_Image();

			if ( $metadata = $fetured->get_attachment_metadata( $settings['post']['featuredImageId'] ) ) {
				$settings['post']['featuredImageId'] = $metadata['id'];
				$settings['post']['featuredImageAccountId'] = $this->token;
			}
		}


		/**
		 * Hook service template upload
		 */
		$settings['tpmr_services'] = array(
			'tpmr' => array(
				'tpl_upload' => 'attachment-upload',
			)
		);

		return $settings;
	}

	public function template_upload_imgur() {
		tpmr_template( 'attachment-upload' );
	}

	public function template_attachment_remoter() {
		tpmr_template( 'attachment-remoter' );
	}

	public function admin_post_thumbnail_html( $content, $post_id, $thumbnail_id ) {

		/**
		 * Remove thumbnail when thumbnail_id is null
		 */
		if ( $thumbnail_id === null ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			wp_delete_post( $thumbnail_id, true );
		} elseif ( get_post_meta( $thumbnail_id, '_is_tpmr', true ) ) {

			$attachment = get_post_meta( $thumbnail_id, '_wp_attachment_metadata', true );

			$featured = new TPMR_Featured_Image( $attachment );

			$content = $featured->post_thumbnail_html( $thumbnail_id, $post_id );
		}

		return $content;
	}

	/**
	 *   Hooked by pre_get_posts
	 *
	 *   Modify query vars on media page (list mode) to hide all medias (attachments) that are identified as _is_tpmr
	 */
	public function modify_attachment_query( $query ) {
		global $pagenow;

		if ( $pagenow != 'upload.php' ) {
			return;
		}
		$post_type = $query->get( 'post_type' );

		if ( $post_type == 'attachment' || (is_array( $post_type ) && in_array( 'attachment', $post_type )) ) {
			$meta_query = $query->get( 'meta_query' );

			$meta_query_args = array(
				'key' => '_is_tpmr',
				'value' => 1,
				'compare' => 'NOT EXISTS',
			);

			//Keep meta query previous args in case it is set already
			if ( empty( $meta_query ) ) {
				$meta_query = array( $meta_query_args );
			} else {
				$meta_query[] = $meta_query_args;
			}
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 *   Hooked by ajax_query_attachments_args (available since 3.7.0) in wp-admin/includes/ajax-actions.php
	 *
	 *   Modify query vars on query-attachments action to hide all medias (attachments) that are identified as _is_tpmr
	 *   unless the request comes for a gallery_shortcode list
	 */
	public function modify_ajax_attachment_query_args( $query ) {
		if ( !empty( $_REQUEST['query'] ) && !empty( $_REQUEST['query']['for'] ) ) {
			$for = sanitize_text_field( $_REQUEST['query']['for'] );
			if ( $for = 'gallery_shortcode' ) {
				return $query;
			}
		}

		$meta_query_args = array(
			'key' => '_is_tpmr',
			'value' => true,
			'compare' => 'NOT EXISTS',
		);

		//In case meta query is already set just add to it
		if ( !empty( $query['meta_query'] ) ) {
			$query['meta_query'][] = $meta_query_args;
			return $query;
		}

		$query['meta_query'] = array( $meta_query_args );

		return $query;
	}

	public function post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

		if ( !get_post_meta( $post_thumbnail_id, '_is_tpmr', true ) ) {
			return $html;
		}

		$attachment = get_post_meta( $post_thumbnail_id, '_wp_attachment_metadata', true );
		$size = isset( $attachment['image_size'] ) ? $attachment['image_size'] : $size;
		$image = array();

		if ( isset( $attachment['sizes'][$size] ) ) {
			$image = $attachment['sizes'][$size];
		}

		$attachment_id = $attachment['id'];

		if ( $image ) {

			list($src, $width, $height) = array( $image['url'], $image['width'], $image['height'] );

			$hwstring = image_hwstring( $width, $height );
			$size_class = $size;
			if ( is_array( $size_class ) ) {
				$size_class = join( 'x', $size_class );
			}
			$default_attr = array(
				'src' => $src,
				'class' => "attachment-$size_class size-$size_class",
				'alt' => trim( strip_tags( $attachment['alt'] ) ),
			);

			$attr = wp_parse_args( $attr, $default_attr );

			// Generate 'srcset' and 'sizes' if not already present.
			if ( empty( $attr['srcset'] ) ) {

				if ( is_array( $attachment ) ) {
					$size_array = array( absint( $width ), absint( $height ) );
					$srcset = wp_calculate_image_srcset( $size_array, $src, $attachment, $attachment_id );
					$sizes = wp_calculate_image_sizes( $size_array, $src, $attachment, $attachment_id );

					if ( $srcset && ( $sizes || !empty( $attr['sizes'] ) ) ) {
						$attr['srcset'] = $srcset;

						if ( empty( $attr['sizes'] ) ) {
							$attr['sizes'] = $sizes;
						}
					}
				}
			}

			/**
			 * Filters the list of attachment image attributes.
			 */
			$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );
			$attr = array_map( 'esc_attr', $attr );
			$html = rtrim( "<img $hwstring" );
			foreach ( $attr as $name => $value ) {
				$html .= " $name=" . '"' . $value . '"';
			}
			$html .= ' />';
		} else {
			$html = '';
		}

		return $html;
	}

}

new TPMR_Hooks();
