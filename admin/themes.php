<?php include('header.php');?>


<div class="container currenttheme">
	<h2>Current Theme</h2>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $active_theme['info']->url; ?>" class="plugin"><?php echo $active_theme['info']->name; ?></a> <span class="version dim"><?php echo $active_theme['info']->version; ?></span> <span class="dim">by</span> <a href="<?php echo $active_theme['info']->url; ?>"><?php echo $active_theme['info']->author; ?></a></span>

			<?php if($configurable): ?>
			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'admin', 'page=themes&configure=' . $active_theme['dir'] ); ?>">Settings</a></li>
			</ul>
			<?php endif; ?>

			<?php if($active_theme['info']->update != ''): ?>
			<ul class="dropbutton alert">
				<li><a href="#">v<?php echo $active_theme['info']->update; ?> Update Available</a></li>
			</ul>
			<?php endif; ?>
		</div>

		<div>
			<div class="thumb pct20"><span><img src="<?php echo $active_theme['screenshot']; ?>"></span></div>

			<p class="description pct80"><?php echo $active_theme['info']->description; ?></p>
			<?php if($active_theme['info']->license != ''): ?>
			<p class="description pct80"><?php echo $active_theme['info']->name; ?> is licensed under the <?php echo $active_theme['info']->license; ?></p>
			<?php endif; ?>
		</div>
	</div>

</div>



<div class="container availablethemes">

	<h2>Available Themes</h2>
<?php
foreach($all_themes as $inactive_theme):
	if ( $inactive_theme['dir'] != $active_theme_dir ) : ?>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <span class="version dim"><?php echo $inactive_theme['info']->version; ?></span></a> <span class="dim">by</span> <a href="<?php echo $inactive_theme['info']->url; ?>" class="author"><?php echo $inactive_theme['info']->author; ?></a></span>

			<ul class="dropbutton">
				<li><form method='post' action='<?php URL::out('admin', 'page=activate_theme'); ?>'>
				<input type='hidden' name='theme_name' value='<?php echo $inactive_theme['info']->name; ?>'>
				<input type='hidden' name='theme_dir' value='<?php echo $inactive_theme['dir']; ?>'>
				<input type='submit' name='submit' value='activate'>
				</form></li>

			</ul>
		</div>

		<div>
			<div class="thumb pct20"><span><img src="<?php echo $inactive_theme['screenshot']; ?>"></span></div>

			<p class="description pct80"><?php echo $inactive_theme['info']->description; ?></p>
			<?php if($inactive_theme['info']->license != ''): ?>
			<p class="description pct80"><?php echo $inactive_theme['info']->name; ?> is licensed under the <?php echo $inactive_theme['info']->license; ?></p>
			<?php endif; ?>
		</div>
	</div>

<?php
	endif;
endforeach;
?>
</div>





<?php include('footer.php');?>
