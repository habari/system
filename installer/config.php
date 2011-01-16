<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include( 'header.php' ); ?>
<form action="" method="post">
<?php
foreach ($this->engine_vars as $key => $val) {
	if ( is_scalar( $val ) ) {
		echo '<input type="hidden" name="' . htmlentities($key, ENT_COMPAT, 'UTF-8') . '" value="' . htmlentities($val, ENT_COMPAT, 'UTF-8') . '">';
	}
}
?>
<input type="hidden" name="locale" value="<?php echo Utils::htmlspecialchars($locale); ?>">

<div class="installstep ready">
	<h2>Config.php<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php _e('Your <strong>config.php</strong> file is not writable. In order to successfully install Habari, please paste the following into'); ?> <strong><?php echo $config_file; ?></strong>:<br />
			<textarea class="config"><?php echo $file_contents; ?></textarea>
			<div class="help">
				<?php _e('<strong>config.php</strong> is a file that tells Habari how to connect to your database. Habari is not able to write this file to your server automatically, so you must create this file yourself to continute the installation.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>
	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready">
	<h2><?php _e('Install'); ?></h2>
	<div class="options">
		<div class="inputfield submit">
			<div><?php _e('When you have successfully placed the config file, run the installer again.'); ?></div>
			<input type="submit" name="submit" value="<?php _e('Restart Installer'); ?>" />
		</div>
	</div>
	<div class="bottom"></div>
</div>
</form>

<script type="text/javascript">
$(document).ready(function() {
	$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
	$('.help').hide();
});
</script>

<?php include( 'footer.php' ); ?>
