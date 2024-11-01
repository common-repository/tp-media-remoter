<?php

/**
 * Template file
 * 
 * @since 1.0.0
 */
function tpmr_template( $slug, $data = array() ) {

	if ( is_array( $data ) ) {
		extract( $data );
	}
	
	include TPMR_DIR . 'templates/' . $slug . '.php';
}

/**
 * Get content of template file
 * 
 * @since 1.0.0
 */
function tpmr_get_template( $slug, $data = array() ) {
	ob_start();
	tpmr_template( $slug, $data );
	return ob_get_clean();
}

/**
 * Admin notice
 *
 * @since 1.0.0
 */
add_action( 'admin_notices', 'tpfw_notice_admin' );
if (!function_exists('tpfw_notice_admin')){
	function tpfw_notice_admin(){

		$logo = sprintf('<img style="width: 30px;height:auto;vertical-align: middle;" src="%s" alt="logo-themepond">',TPMR_URL.'/assets/images/logo-tp.png');
		$class = 'notice tp-notice';
		$message = sprintf(__('Explore more about our products such as: PSD Templates, Premium Plugins, WordPress Themes,... on ThemesPond. <a href="%s" target="_blank">View Now!</a>','tp-media-remoter'),esc_url('https://www.themespond.com/'));

		echo wp_kses_post( sprintf( '<div class="%1$s"><p>%3$s %2$s</p></div>', $class , $message , $logo));

	}

}