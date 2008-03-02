<?php include('header.php');?>
<div class="container">
	<hr>
	<?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column prepend-1 span-22 append-1">
		<h2 class="center">Currently Available Themes</h2>
		<p class="center">Activate, deactivate, configure and remove themes through this interface.</p>
	</div>
		<?php
		if ( isset( $this->engine_vars['configure'] ) ): ?>
			</div>
				<div id="theme_options"><div class="container"><div class="column prepend-1 span-22 append-1">
					<h2><?php echo $active_theme_name ?> : Configure</h2>
					<?php Plugins::act( 'theme_ui', $active_theme ); ?>
					<a class="link_as_button" href="<?php URL::out( 'admin', 'page=themes' ); ?>"><?php echo 'close' ?></a>
				</div></div></div>
			<div class="container">
		<?php endif; ?>
	<div class="column prepend-1 span-22 append-1">
		<?php foreach( $all_themes as $theme ) : ?>
			<div class="screenshot">
				<img src="<?php echo $theme['screenshot']; ?>" width="200" height="150" /><br>
				<b><?php echo $theme['info']->name; ?> <?php echo $theme['info']->version; ?></b><br>
				 by <a href="<?php echo $theme['info']->url; ?>"><?php echo $theme['info']->author; ?></a>

				<?php if ( $theme['dir'] != $active_theme_dir ) { ?>
				<form method='post' action='<?php URL::out('admin', 'page=activate_theme'); ?>'>
				<input type='hidden' name='theme_name' value='<?php echo $theme['info']->name; ?>'>
				<input type='hidden' name='theme_dir' value='<?php echo $theme['dir']; ?>'>
				<input type='submit' name='submit' value='activate'>
				</form>
				<?php } else {
						echo "<br><strong>Currently Active Theme</strong><br><br>";
						if ( $configurable ) {
						?>
							<a class="link_as_button" href="<?php URL::out( 'admin', 'page=themes&configure=' . $theme['dir'] ); ?>">Configure</a>
						<?php
						}
				  }
				 ?>

			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php include('footer.php');?>
