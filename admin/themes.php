<?php include('header.php');?>
<div class="container">
	<hr>
	<?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column prepend-1 span-22 append-1">
		<h2>Currently Available Themes</h2>
		<p>Activate, deactivate and remove themes through this interface.</p>
	</div>
	<div class="column prepend-1 span-22 append-1">
		<?php foreach( $all_themes as $theme ) : ?>
			<div class="screenshot">
				<img src="<?php echo $theme['screenshot']; ?>" width="200" height="150" /><br>
				<b><?php echo $theme['info']->name; ?> <?php echo $theme['info']->version; ?></b><br>
				 by <a href="<?php echo $theme['info']->url; ?>"><?php echo $theme['info']->author; ?></a>

				<?php if ( $theme['dir'] != $active_theme ) { ?>
				<form method='post' action='<?php URL::out('admin', 'page=activate_theme'); ?>'>
				<input type='hidden' name='theme_name' value='<?php echo $theme['info']->name; ?>'>
				<input type='hidden' name='theme_dir' value='<?php echo $theme['dir']; ?>'>
				<input type='submit' name='submit' value='activate'>
				</form>	
				<?php } else {
					echo "<br><strong>Currently Active Theme</strong><br><br>";
				  }
				 ?>
				
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php include('footer.php');?>
