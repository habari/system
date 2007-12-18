<?php include( 'header.php' ); ?>
<div class="container">
<hr>
  <?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column prepend-1 span-22 append-1" id="welcome">
		<h2>Currently Available Plugins</h2>
		<p>Activate, deactivate and remove plugins through this interface.</p>
	</div>
	<div class="column prepend-1 span-22 append-1">
		<table cellspacing="0" width="100%">
			<thead>
				<tr>
					<th align="left">Plugin Name</th>
					<th align="left">Author Name</th>
					<th align="left">Version</th>
					<th align="left">Description</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($plugins as $plugin) {
				if($plugin['debug']) {
				?>
					<tr>
						<td colspan="3" class="error"><p>The plugin file '<?php echo $plugin['file']; ?>' had syntax errors and could not load.</p>
						<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
						</td>
						<td><button onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();">Show Error</button></td>
					</tr>
				<?php
				}
				else {
				?>
					<tr id="plugin_<?php echo $plugin['plugin_id']; ?>">
						<td><?php echo '<a href="' . $plugin['info']->url . '">' . $plugin['info']->name . '</a>'; ?>
						</td>
						<td><?php echo empty( $plugin['info']->authorurl ) ? $plugin['info']->author : '<a href="' . $plugin['info']->authorurl . '">' . $plugin['info']->author . '</a>'; ?></td>
						<td><?php echo $plugin['info']->version; ?></td>
						<td><?php echo $plugin['info']->description; ?></td>
						<td>
						<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
						<p><input type='hidden' name='plugin' value='<?php echo $plugin['file']; ?>'>
						<input type='hidden' name='action' value='<?php echo $plugin['active'] ? 'Deactivate' : 'Activate'; ?>'>
						<button name='submit' type='submit'><?php echo $plugin['verb']; ?></button>
						<?php
						if ( $plugin['active'] ) {
							$plugin_actions= array();
							$plugin_actions= Plugins::filter( 'plugin_config', $plugin_actions, $plugin['plugin_id'] );
							foreach( $plugin['actions'] as $plugin_action => $plugin_action_caption ) {
								if( isset($configure) && ($configure == $plugin['plugin_id']) && ($action == $plugin_action) ) {
									continue;
								}
								if ( is_numeric( $plugin_action ) ) {
									$plugin_action = $plugin_action_caption;
								}
								?>
								<a class="link_as_button" href="<?php URL::out( 'admin', 'page=plugins&configure=' . $plugin['plugin_id'] . '&action=' . $plugin_action ); ?>#plugin_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin_action_caption; ?></a>
								<?php
							}
						}
						?>
						</p>
						</form>
						</td>
					</tr>
					<?php if ( isset( $this->engine_vars['configure'] ) && ($configure == $plugin['plugin_id'])) { ?>
					</tbody></table></div></div></div>
					<div id="plugin_options"><div class="container"><div class="column prepend-1 span-22 append-1">
						<h2><?php echo $plugins[$configure]['info']->name; ?> : <? echo $action; ?></h2>
						<?php
							Plugins::act( 'plugin_ui', $configure, $action );
						?>
					</div></div></div>
					<div class="wrapper">
					<div class="container">
					<div class="column prepend-1 span-22 append-1">
					<table cellspacing="0" width="100%"><tbody>
					<?php } ?>
				<?php } ?>
			<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php include( 'footer.php' ); ?>
