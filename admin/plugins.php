<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>

<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.filter();" tabindex="1">
			<option value="all"><?php _e('All plugins'); ?></option>
			<?php if ( isset($config_plugin) ): ?>
				<option value="configureplugin"><?php echo $config_plugin_caption; ?></option>
			<?php endif; ?>
			<?php if ( count($active_plugins) > 0 ): ?>
				<option value="activeplugins"><?php _e('Active Plugins'); ?></option>
			<?php endif; ?>
			<?php if ( count($inactive_plugins) > 0 ): ?>
				<option value="inactiveplugins"><?php _e('Inactive Plugins'); ?></option>
			<?php endif; ?>
		</select>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" id="search" placeholder="<?php _e('search plugins'); ?>" tabindex="2" autofocus="autofocus">
	</span>
</div>

<?php
if ( isset($config_plugin) ): ?>

<div class="container plugins configureplugin" id="configureplugin">

	<h2><?php echo $config_plugin['info']->name; ?> &middot; <?php echo $config_plugin_caption; ?></h2>

	<?php

	$theme->config = true;
	$theme->plugin = $config_plugin;
	$theme->display('plugin');

	$theme->config = false;

	?>

</div>

<?php endif; ?>


<?php if ( count($active_plugins) > 0 ): ?>

<div class="container plugins activeplugins" id="activeplugins">

	<h2><?php _e('Active Plugins'); ?></h2>

	<?php
	foreach ( $active_plugins as $plugin ) {
		$theme->plugin = $plugin;
		$theme->display('plugin');
	}
	?>

</div>
<?php endif; ?>

<?php if ( count($inactive_plugins) > 0 ): ?>
<div class="container plugins inactiveplugins" id="inactiveplugins">

	<h2><?php _e('Inactive Plugins'); ?></h2>
	
	<?php
	foreach ( $inactive_plugins as $plugin) {
		$theme->plugin = $plugin;
		$theme->display('plugin');
	}
	?>

</div>
<?php endif; ?>

<?php if ( isset($plugin_loader) && $plugin_loader != '' ): ?>
<?php echo $plugin_loader; ?>
<?php endif; ?>

<!--<div class="container uploadpackage">
	<h2><?php _e('Upload Plugin Package'); ?></h2>
	<div class="uploadform">
		<input type="file">
		<input type="submit" value="<?php _e('Upload'); ?>">
	</div>
</div>-->

<?php include('footer.php'); ?>
