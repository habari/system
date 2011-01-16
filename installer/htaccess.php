<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include( 'header.php' ); ?>

<form action="" method="post">
<input type="hidden" name="locale" value="<?php echo Utils::htmlspecialchars($locale); ?>">

<div class="installstep ready">
	<h2>.htaccess<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php _e('Your <strong>.htaccess</strong> file is not writable. In order to successfully install Habari, please paste the following into'); ?> <strong><?php echo HABARI_PATH . '/.htaccess'; ?></strong>:<br>
			<textarea class="config" tabindex="<?php echo $tab++ ?>"><?php echo $file_contents; ?></textarea>
			<div class="help">
				<?php _e('<strong>.htaccess</strong> is a file that tells your Apache web server to send requests to Habari for handling. Habari is not able to write this file to your server automatically, so you must create this file yourself to continue the installation.'); ?>
				<a onclick="this.target='_blank';" href="<?php Site::out_url( 'habari' ); ?>/manual/index.html#Installation"><?php _e('Learn More&hellip;'); ?></a>
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
			<p><?php _e('When you have successfully placed the .htaccess file, run the installer again.'); ?></p>
			<input type="submit" name="submit" value="<?php _e('Restart Installer'); ?>" tabindex="<?php echo $tab++ ?>">
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
