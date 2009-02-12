<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php include( 'header.php' ); ?>

<form action="" method="post">
<input type="hidden" name="locale" value="<?php echo htmlspecialchars($locale); ?>">

<div class="installstep ready">
	<h2>Config.php<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php _e('Your <strong>config.php</strong> file is not writable. In order to successfully install Habari, please paste the following into'); ?> <strong><?php echo $config_file; ?></strong>:<br />
			<textarea class="config"><?php echo $file_contents; ?></textarea>
			<div class="help">
				<?php _e('<strong>config.php</strong> is a file that tells Habari how to connect to your database. Habari is not able to write this file to your server automatically, so you must create this file yourself to continute the installation.'); ?>
				<a href="#"><?php _e('Learn More...'); ?></a>
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