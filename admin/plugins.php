<?php include('header.php'); ?>

<?php if ( count($active_plugins) > 0 ): ?>

<div class="container plugins activeplugins">

	<h2><?php _e('Active Plugins'); ?></h2>

	<?php foreach($active_plugins as $plugin) { if($plugin['debug']) { ?>
	<div class="item clear">
		<div class="head">
			<p><?php echo _t('The plugin file ') . $plugin['file'] . _t(' had syntax errors and could not load.'); ?></p>
			<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
				<ul class="dropbutton">
					<li><a href="#" onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();"><?php _e('Show Error'); ?></a></li>
				</ul>
		</div>
	</div>

	<?php } else { ?>

	<div class="item clear" id="plugin_<?php echo $plugin['plugin_id']; ?>">
		<div class="head">
			<a href="<?php echo $plugin['info']->url; ?>" class="plugin"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></a> <span class="dim">by</span> <?php echo empty( $plugin['info']->authorurl ) ? $plugin['info']->author : '<a href="' . $plugin['info']->authorurl . '">' . $plugin['info']->author . '</a>'; ?>

			<ul class="dropbutton">

				<?php
				if ( $plugin['active'] ) {
					$plugin_actions= array();
					$plugin_actions= Plugins::filter( 'plugin_config', $plugin_actions, $plugin['plugin_id'] );
					foreach( $plugin['actions'] as $plugin_action => $plugin_action_caption ) {
						if( isset($configure) && ($configure == $plugin['plugin_id']) && ($action == $plugin_action) )
							continue;

						if ( is_numeric( $plugin_action ) )
							$plugin_action = $plugin_action_caption;
				?>
						<li><a href="<?php URL::out( 'admin', 'page=plugins&configure=' . $plugin['plugin_id'] . '&action=' . $plugin_action ); ?>#plugin_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin_action_caption; ?></a></li>
				<?php } } ?>


				<li>
					<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
					<input type='hidden' name='plugin' value='<?php echo $plugin['file']; ?>'>
					<input type='hidden' name='action' value='<?php echo $plugin['active'] ? _t('Deactivate') : _t('Activate'); ?>'>
					<button name='submit' type='submit'><?php echo $plugin['verb']; ?></button></form>
				</li>

			</ul>


			<?php if( isset( $updates ) ) { ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v1.1 Update Available Now'); ?></a></li>
			</ul>
			<?php } ?>

		</div>

		<p class="description"><?php echo $plugin['info']->description; ?></p>

		<?php if ( isset( $this->engine_vars['configure'] ) && ( $configure == $plugin['plugin_id'] ) ) { ?>
		<div id="pluginconfigure">
			<?php Plugins::act( 'plugin_ui', $configure, $action ); ?>
			<a class="link_as_button" href="<?php URL::out( 'admin', 'page=plugins' ); ?>"><?php _e('close'); ?></a>
		</div>
		<?php } ?>

	<?php } ?>
	</div>

	<?php } ?>

</div>
<?php endif; ?>

<?php if ( count($inactive_plugins) > 0 ): ?>
<div class="container plugins inactiveplugins">

	<h2><?php _e('Inactive Plugins'); ?></h2>

	<?php foreach($inactive_plugins as $plugin) { if($plugin['debug']) { ?>
	<div class="item clear">
		<div class="head">
			<p><?php echo _t('The plugin file ') . $plugin['file'] . _t(' had syntax errors and could not load.'); ?></p>
			<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
				<ul class="dropbutton">
					<li><a href="#" onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();"><?php _e('Show Error'); ?></a></li>
				</ul>
		</div>
	</div>
	<?php } else { ?>

	<div class="item clear" id="plugin_<?php echo $plugin['plugin_id']; ?>">
		<div class="head">
			<a href="<?php echo $plugin['info']->url; ?>" class="plugin"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></a> <span class="dim">by</span> <?php echo empty( $plugin['info']->authorurl ) ? $plugin['info']->author : '<a href="' . $plugin['info']->authorurl . '">' . $plugin['info']->author . '</a>'; ?>

			<ul class="dropbutton">
				<li>
				<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
				<input type='hidden' name='plugin' value='<?php echo $plugin['file']; ?>'>
				<input type='hidden' name='action' value='<?php echo $plugin['active'] ? _t('Deactivate') : _t('Activate'); ?>'>
				<button name='submit' type='submit'><?php echo $plugin['verb']; ?></button>
			    </li></form>
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

<div class="container uploadpackage">
	<h2><?php _e('Upload Plugin Package'); ?></h2>
	<div class="uploadform">
		<input type="file">
		<input type="submit" value="<?php _e('Upload'); ?>">
	</div>
</div>

<?php include('footer.php'); ?>
