<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include( 'header.php' ); ?>

<form action="" method="post">
<input type="hidden" name="locale" value="<?php echo Utils::htmlspecialchars($locale); ?>">

<div class="installstep ready">
	<h2>.htaccess<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php _e('Your <strong>.htaccess</strong> file is not writable. In order to secure your SQLite database, please paste the following into <strong>%s</strong>:', array(HABARI_PATH . '/.htaccess')); ?><br />
			<textarea class="config"><?php echo $sqlite_contents; ?></textarea>
			<div class="help">
				<?php _e('Your SQLite database is a file on your server like any other file. You can enhance it\'s security by including a section in your .htaccess file that disallows access to it by readers on the web. Habari is not able to write this section in your .htaccess file automatically, so you must add this section yourself to gain the enhanced security it offers you.'); ?>
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
			<div><?php _e('When you have successfully placed the .htaccess file, run the installer again.'); ?></div>
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
