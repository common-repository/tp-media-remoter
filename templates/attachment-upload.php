<script type="text/html" id="tmpl-attachment-upload">
	<div class="uploader-inline rm-upload-wrap">
		<div  id="tpmr_media_uploader" class="uploader-inline-content no-upload-message">
			<div class="upload-ui">

				<h3 class="upload-instructions drop-instructions"><?php echo esc_html__( 'Select an image to upload directly to TP Media Remoter', 'tp-media-remoter' ); ?></h3>

				<a href="#" class="browser button button-hero" style="display: inline;" id="tpmr_upload_select"><?php echo esc_html__( 'Select File','tp-media-remoter' ); ?></a>

				<input style="display: none;" type="file" id="tpmr_files" name="tpmr_files" value="{{data.filename}}" />

				<div class="upload-inline-status"></div>

				<div class="post-upload-ui">
					<p class="max-upload-size" style="display:none;">
						<?php printf( __( 'Maximum upload file size: %s.', 'tp-media-remoter' ), '<span id="tpmr_filesize"></span>' ); ?>
					</p>
				</div>

				<div id="uploadStatus"></div>

			</div>

			<div class="tpmr-post-file-select">

				<div class="tpmr-form-field">
					<h2><?php echo esc_html__( 'Picture Settings', 'tp-media-remoter' ) ?></h2>
				</div>

				<div class="tpmr-file_preview">
					<img src="" alt=""/>
					<a href="#" class="tpmr-cancel_upload" title="<?php echo esc_html__( 'Cancel upload', 'tp-image-remoter' ) ?>">x</a>
				</div>

				<div class="tpmr-form-field">
					<label for="tpmr_title"><?php echo esc_html__( 'Title:', 'tp-media-remoter' ) ?></label>
					<input id="tpmr_title" type="text" class="regular-text" placeholder="<?php echo esc_attr__( 'Enter title', 'tp-media-remoter' ) ?>">
				</div>

				<div class="tpmr-form-field">
					<label for="tpmr_description"><?php echo esc_html__( 'Description:', 'tp-media-remoter' ) ?></label>
					<textarea id="tpmr_description" placeholder="<?php echo esc_attr__( 'Enter description', 'tp-media-remoter' ) ?>"></textarea>
				</div>

				<a href="#" class="button button-primary button-hero" id="tpmr_upload_submit"><?php echo esc_html__( 'Start Upload', 'tp-media-remoter' ); ?></a>   

			</div>
		</div>
	</div>
</script>