<?php include( 'header.php' ); ?>
<div class="container">
<hr>
  <?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column prepend-1 span-22 append-1" id="welcome">
		<h2>Currently Available Plugins</h2>
		<p>Activate, deactivate and remove plugins through this interface.</p>
	</div>
	<div class="column prepend-1 span-22 append-1">
		<?php
			$listok= true;
			$all_plugins= Plugins::list_all();
			$active_plugins= Plugins::get_active();

			if ( Plugins::changed_since_last_activation() ) {
				if ( Plugins::check_every_plugin_syntax() ) {
					$listok= false;
				}
			}
			Options::clear_cache();
			$failed_plugins= Options::get('failed_plugins');
			if ( is_array( $failed_plugins ) ) {
				$all_plugins= array_diff( $all_plugins, $failed_plugins );
			}
			if ( $listok ) {
				if ( count( $failed_plugins ) > 0 ) {
					echo '<div class="warning">';
					foreach( $failed_plugins as $failed ) {
						echo '<p>' . sprintf( _t( 'Attempted to load the plugin file "%s", but it failed with syntax errors.' ), basename( $failed ) ) . '</p>';
					}
					echo '</div>';
				}
			}
		?>
		<table cellspacing="0" width="100%">
			<thead>
				<tr>
					<th align="left">Plugin Name</th>
					<th align="left">Author Name</th>
					<th align="left">Version</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $all_plugins as $file ) {
				$verb= _t( 'Activate' );
				$plugin_id= Plugins::id_from_file( $file );
				if ( array_key_exists( $plugin_id, $active_plugins ) ) {
					$verb= _t( 'Deactivate' );
					$plugin= $active_plugins[$plugin_id];
					$active= true;
				}
				else {
					// instantiate this plugin
					// in order to get its info()
					include_once( $file );
					$plugin= Plugins::load( $file );
					$active= false;
				}
				if (isset( $plugin->info->url ) ) {
					$url= "<p><a href=\"{$plugin->info->url}\" title=\"Visit " . str_replace('"', '\\"', $plugin->info->name) . "\">{$plugin->info->author}</a></p>";
				}
				else {
					$url= $plugin->info->author;
				}
			?>
				<tr>
					<td><?php echo $plugin->info->name; ?>
					</td>
					<td><?php echo $url; ?></td>
					<td><?php echo $plugin->info->version; ?></td>
					<td>
					<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
					<input type='hidden' name='plugin' value='<?php echo $file; ?>'>
					<input type='hidden' name='action' value='<?php echo $active ? 'Deactivate' : 'Activate'; ?>'>
					<p><button name='submit' type='submit'><?php echo $verb; ?></button>
					<?php
					if ( $active ) {
						$plugin_actions= array();
						$plugin_actions= Plugins::filter( 'plugin_config', $plugin_actions, $plugin->plugin_id );
						foreach( $plugin_actions as $plugin_action => $plugin_action_caption ) {
							if( isset($configure) && ($configure == $plugin->plugin_id) && ($action == $plugin_action) ) {
								continue;
							}
							if ( is_numeric( $plugin_action ) ) {
								$plugin_action = $plugin_action_caption;
							}
							?>
							<a class="link_as_button" href="<?php URL::out( 'admin', 'page=plugins&configure=' . $plugin->plugin_id . '&action=' . $plugin_action ); ?>#plugin_options"><?php echo $plugin_action_caption; ?></a>
							<?php
						}
					}
					?>
					</p>
					</form>
					</td>
				</tr>
				<?php if ( isset( $this->engine_vars['configure'] ) && ($configure == $plugin->plugin_id)) { ?>
				</tbody></table></div></div>
				<div id="plugin_options"><div class="container"><div class="column prepend-1 span-22 append-1">
					<h2><?php echo $active_plugins[$configure]->info->name; ?> : <? echo $action; ?></h2>
					<?php
						Plugins::act( 'plugin_ui', $configure, $action );
					?>
				</div></div>
				<div class="wrapper">
				<div class="container">
				<div class="column prepend-1 span-22 append-1">
				<table cellspacing="0" width="100%"><tbody>
				<?php } ?>
			<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php include( 'footer.php' ); ?>
