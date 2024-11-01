<?php
$user = new TPMR_User();

$registerd_email = $user->get_email();
$registerd_token = $user->get_token();

$form_current = empty( $registerd_email ) ? 0 : 1;

if ( !empty( $registerd_token ) ) {
	$form_current = 2;
}

$email = empty( $registerd_email ) ? get_option( 'admin_email' ) : $registerd_email;
?>

<div class="tpui">
	<div class="tpui-header">
		
		<div class="tpui-header-left">
			<div class="logo"><img src="<?php echo esc_url( TPMR_URL . 'assets/images/logo.png' ) ?>" alt="tpmr"></div>
			<div class="description">
				<h3><?php echo esc_html__( 'TP Media Remoter', 'tp-media-remoter' ) ?></h3>
				<span><?php echo esc_html__( 'An effective and safe external library to save your hosting storage.', 'tp-media-remoter' ) ?></span>
			</div>
		</div>
		
		<div class="tpui-header-right">
			<ul>
				<li class="tp-header-icon tp-rate"><a href="https://wordpress.org/support/plugin/tp-media-remoter/reviews/#new-post" target="_blank"><i class="ion-android-star"></i>

						<div class="tp-notice">
							<span><?php echo esc_html__( 'Rating now!', 'tp-media-remoter' ) ?></span>
						</div>
					</a>
				</li>
				
				<li class="tp-header-icon tp-social">
					<a href="#"><i class="ion-android-share-alt"></i></a>
					<div class="tp-notice">
						<a href="https://www.facebook.com/sharer/sharer.php?u=https%3A//wordpress.org/plugins/tp-media-remoter/" target="_blank"><i class="ion-social-facebook"></i></a>
						<a href="https://twitter.com/home?status=%23wordpress,%20%23tp-media-remoter,%20%23plugin%0A%0Ahttps%3A//plus.google.com/share?url=https%253A//wordpress.org/plugins/tp-media-remoter/" target="_blank"><i class="ion-social-twitter"></i></a>
						<a href="https://plus.google.com/share?url=https%3A//wordpress.org/plugins/tp-media-remoter/" target="_blank"><i class="ion-social-googleplus-outline"></i></a>
						<a href="https://pinterest.com/pin/create/button/?url=https%3A//www.facebook.com/sharer/sharer.php?u=https%253A//wordpress.org/plugins/tp-media-remoter/&media=&description=Insert%20featured%20image%20and%20media%20to%20Editor%20using%20WordPress%20Media%20Library%20with%20a%20external%20library.%20The%20best%20way%20to%20save%20your%20hosting%20storage.%20" target="_blank"><i class="ion-social-pinterest"></i></a>
					</div>
				</li>
				
				<li class="tp-header-icon tp-support">
					<a href="https://themespond.com/" target="_blank"><i class="ion-chatbubble-working"></i>
						<div class="tp-notice">
							<span><?php echo esc_html__( 'Live Chat Support', 'tp-media-remoter' ) ?></span>
						</div>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<div class="tp-installer tp-installer--steps" data-step="<?php echo esc_attr( $form_current ) ?>">

		<div class="tp-installer__nav tp-installer__nav--3">
			<span class="tp-installer__progress"></span>
			<div data-step class="tp-installer__navitem">
				<span>1</span>
				<p><?php echo esc_html__( 'Get a product key via email', 'tp-media-remoter' ) ?> </p>
			</div>
			<div data-step class="tp-installer__navitem">
				<span>2</span>
				<p><?php echo esc_html__( 'Validate product key from the email', 'tp-media-remoter' ) ?></p>
			</div>
			<div data-step class="tp-installer__navitem">
				<span>3</span>
				<p><?php echo esc_html__( 'Your product key is activated', 'tp-media-remoter' ) ?></p>
			</div>
		</div>

		<div class="tp-installer__forms">

			<div data-form="0" class="form-item">
				<input type="email" name="email" class="tp-input" value="<?php echo esc_attr( $email ) ?>"/>
				<ul class="tp-errors"></ul>
				<button class="js-get-token tp-btn-primary" type="submit"><?php echo esc_html__( 'Get a free key', 'tp-media-remoter' ) ?></button>
				<?php wp_nonce_field( 'tpmr_register_token', 'nonce_field_get_token' ) ?>
				<p><?php echo esc_html__( 'If you have a product key?', 'tp-media-remoter' ) ?> <a data-forward href="#"><?php echo esc_html__( 'Active now', 'tp-media-remoter' ) ?></a></p>
				
			</div>

			<div data-form="1" class="form-item">
				<input class="tp-input" type="text" name="token" placeholder="<?php echo esc_attr__( 'Enter product key', 'tp-media-remoter' ) ?>" value=""/>
				<input type="hidden" name="email" value="<?php echo esc_attr( $email ) ?>"/>
				<ul class="tp-errors"></ul>
				<button class="js-validate-token tp-btn-primary" type="submit"><?php echo esc_html__( 'Validate key', 'tp-media-remoter' ) ?></button>
				<?php wp_nonce_field( 'tpmr_validate_token', 'nonce_field_validate_token' ) ?>

				<p><?php echo esc_html__( 'Do you received a product key from email?', 'tp-media-remoter' ) ?> <a data-back href="#"><?php echo esc_html__( 'Back to resend email', 'tp-media-remoter' ) ?></a></p>

			</div>

			<div data-form="2" class="form-item">

				<?php
				$cssClass = 'validate-result';
				if ( $user->get_token() != '' && $user->get_email() != '' && !$user->is_validate() ) {
					$cssClass .= ' validate-result--failure';
				}
				?>

				<div class="<?php echo esc_attr( $cssClass ) ?>">

					<div class="frm-change-code">

						<input class="tp-input" disabled type="text" name="token" placeholder="<?php echo esc_attr__( 'Enter new product key', 'tp-media-remoter' ) ?>" value="<?php echo esc_attr( $registerd_token ) ?>"/>
						<input type="hidden" name="email" value="<?php echo esc_attr( $email ) ?>"/>
						<ul class="tp-errors"></ul>
						<button class="js-change-token tp-btn-primary" data-update="<?php echo esc_attr__( 'Update key', 'tp-media-remoter' ) ?>"><?php echo esc_html__( 'Change key', 'tp-media-remoter' ) ?></button>

						<?php wp_nonce_field( 'tpmr_validate_token', 'nonce_field_change_token' ) ?>

						<p class="action-buttons">
							<a href="#" class="cancel_update_token"><?php echo esc_html__( 'Cancel update', 'tp-media-remoter' ) ?></a>
							<a href="#" class="create_new_token" data-back="0"><?php echo esc_html__( 'Register new key', 'tp-media-remoter' ) ?></a>
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="image-person"><img src="<?php echo esc_url( TPMR_URL . 'assets/images/hero.png' ) ?>"></div>	

	</div>
</div>