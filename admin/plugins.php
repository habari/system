<?php include('header.php'); ?>

<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.filter();" tabindex="1">
			<option value="all"><?php _e('All plugins'); ?></option>
			<?php if ( count($active_plugins) > 0 ): ?>
				<option value="activeplugins">Active Plugins</option>
			<?php endif; ?>
			<?php if ( count($inactive_plugins) > 0 ): ?>
				<option value="inactiveplugins">Inactive Plugins</option>
			<?php endif; ?>
		</select>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" id="search" placeholder="<?php _e('search plugins'); ?>" autosave="habarisettings" results="10" tabindex="2">
	</span>
</div>


<?php
if(isset($config_plugin)):
?>

<div class="container plugins activeplugins" id="configureplugin">

	<h2><?php echo $config_plugin['info']->name; ?> &middot; <?php echo $config_plugin_caption; ?></h2>

	<div class="item plugin clear" id="plugin_<?php echo $config_plugin['plugin_id']; ?>">
		<div class="head">
			<a href="<?php echo $config_plugin['info']->url; ?>" class="plugin"><?php echo $config_plugin['info']->name; ?> <span class="version"><?php echo $config_plugin['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <?php echo empty( $config_plugin['info']->authorurl ) ? $config_plugin['info']->author : '<a href="' . $config_plugin['info']->authorurl . '">' . $config_plugin['info']->author . '</a>'; ?>
			<?php if( isset($config_plugin['help']) ): ?>
			<a class="help" href="<?php echo $config_plugin['help']['url']; ?>">?</a>
			<?php endif; ?>
			<ul class="dropbutton">

<?php foreach( $config_plugin['actions'] as $plugin_action => $action ) : ?>
						<li><a href="<?php echo $action['url']; ?>"><?php echo $action['caption']; ?></a></li>
<?php endforeach; ?>

			</ul>


			<?php if( isset( $config_plugin['update'] ) ) { ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v1.1 Update Available Now'); ?></a></li>
			</ul>
			<?php } ?>

		</div>

		<p class="description"><?php echo $config_plugin['info']->description; ?></p>

		<div id="pluginconfigure">
			<?php Plugins::act( 'plugin_ui', $configure, $configaction ); ?>
			<a class="link_as_button" href="<?php URL::out( 'admin', 'page=plugins' ); ?>"><?php _e('Close'); ?></a>
		</div>

	</div>
</div>
<?php endif; ?>


<?php if ( count($active_plugins) > 0 ): ?>

<div class="container plugins activeplugins" id="activeplugins">

	<h2><?php _e('Active Plugins'); ?></h2>

	<?php foreach($active_plugins as $plugin) { if($plugin['debug']) { ?>
	<div class="item plugin clear">
		<div class="head">
  			<p><?php printf( _t('The plugin file %s had syntax errors and could not load.'), $plugin['file'] ); ?></p>
			<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
				<ul class="dropbutton">
					<li><a href="#" onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();"><?php _e('Show Error'); ?></a></li>
				</ul>
		</div>
	</div>

	<?php } else { ?>

	<div class="item plugin clear" id="plugin_<?php echo $plugin['plugin_id']; ?>">
		<div class="head">
			<a href="<?php echo $plugin['info']->url; ?>" class="plugin"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <?php echo empty( $plugin['info']->authorurl ) ? $plugin['info']->author : '<a href="' . $plugin['info']->authorurl . '">' . $plugin['info']->author . '</a>'; ?>
			<?php if( isset($plugin['help']) ): ?>
			<a class="help" href="<?php echo $plugin['help']['url']; ?>">?</a>
			<?php endif; ?>
			<ul class="dropbutton">

<?php foreach( $plugin['actions'] as $plugin_action => $action ) : ?>
						<li><a href="<?php echo $action['url']; ?>"><?php echo $action['caption']; ?></a></li>
<?php endforeach; ?>


			</ul>


			<?php if( isset( $plugin['update'] ) ) { ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v1.1 Update Available Now'); ?></a></li>
			</ul>
			<?php } ?>

		</div>

		<p class="description"><?php echo $plugin['info']->description; ?></p>

		<?php if ( isset( $this->engine_vars['configure'] ) && ( $configure == $plugin['plugin_id'] ) ) { ?>
		<div id="pluginconfigure">
			<?php Plugins::act( 'plugin_ui', $configure, $configaction ); ?>
			<a class="link_as_button" href="<?php URL::out( 'admin', 'page=plugins' ); ?>"><?php _e('Close'); ?></a>
		</div>
		<?php } ?>

	<?php } ?>
	</div>

	<?php } ?>

</div>
<?php endif; ?>

<?php if ( count($inactive_plugins) > 0 ): ?>
<div class="container plugins inactiveplugins" id="inactiveplugins">

	<h2><?php _e('Inactive Plugins'); ?></h2>

	<?php foreach($inactive_plugins as $plugin) { if($plugin['debug']) { ?>
	<div class="item plugin clear">
		<div class="head">
			<p><?php printf( _t('The plugin file %s had syntax errors and could not load.'), $plugin['file'] ); ?></p>
			<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
				<ul class="dropbutton">
					<li><a href="#" onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();"><?php _e('Show Error'); ?></a></li>
				</ul>
		</div>
	</div>
	<?php } else { ?>

	<div class="item plugin clear" id="plugin_<?php echo $plugin['plugin_id']; ?>">
		<div class="head">
			<a href="<?php echo $plugin['info']->url; ?>" class="plugin"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <?php echo empty( $plugin['info']->authorurl ) ? $plugin['info']->author : '<a href="' . $plugin['info']->authorurl . '">' . $plugin['info']->author . '</a>'; ?>

			<ul class="dropbutton">
<?php foreach( $plugin['actions'] as $plugin_action => $action ) : ?>
						<li><a href="<?php echo $action['url']; ?>"><?php echo $action['caption']; ?></a></li>
<?php endforeach; ?>
			</ul>

			<?php if( isset( $updates ) ) { ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v1.1 Update Available Now'); ?></a></li>
			</ul>
			<?php } ?>

		</div>

		<p class="description"><?php echo $plugin['info']->description; ?></p>

	</div>
	<?php } ?>
<?php } ?>

</div>
<?php endif; ?>

<!--<div class="container uploadpackage">
	<h2><?php _e('Upload Plugin Package'); ?></h2>
	<div class="uploadform">
		<input type="file">
		<input type="submit" value="<?php _e('Upload'); ?>">
	</div>
</div>-->

<?php include('footer.php'); ?>