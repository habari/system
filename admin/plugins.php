<?php include( 'header.php' ); ?>
<div class="container">
<hr>
	<div class="column prepend-1 span-22 append-1">
		<h2>Currently Available Plugins</h2>
		<p>Activate, deactivate and remove plugins through this interface.</p>
	</div>
	<div class="column prepend-1 span-22 append-1">
		<?php 
			$listok= true;
			$all_plugins= Plugins::list_all();
			$active_plugins= Plugins::get_active();
			if ( Plugins::changed_since_last_activation() ) {
				$request= new RemoteRequest( URL::get( 'admin', array( 'page' => 'loadplugins' ) ), 'POST', 300 );
				$request->add_header( array( 'Cookie' => $_SERVER['HTTP_COOKIE'] ) );
				$result= $request->execute();
				if ( !$request->executed() || preg_match( '%^http/1\.\d 500%i', $request->get_response_headers() ) ) {
					$listok= false;
		?>
			<p><?php _e( 'The plugin list has changed since the last activation.  Please wait while Habari loads the plugin list.' ); ?></p>
		<?php
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
					<p><button name='submit'><?php echo $verb; ?></button></p>
					<?php 
					if ( $active ) {
						$plugin_actions= array();
						$plugin_actions= Plugins::filter( 'plugin_config', $plugin_actions, $plugin->plugin_id );
						foreach( $plugin_actions as $plugin_action => $plugin_action_caption ) {
							if ( is_numeric( $plugin_action ) ) {
								$plugin_action = $plugin_action_caption;
							}
							?>
							<p><a href="<?php URL::out( 'admin', 'page=plugins&configure=' . $plugin->plugin_id . '&action=' . $plugin_action ); ?>"><?php echo $plugin_action_caption; ?></a></p>
							<?php
						}
					}
					?>
					</form>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php } ?>
	</div>
	<?php if ( isset( $this->engine_vars['configure'] ) ) { ?>
	<div class="column span-24" id="plugin_options">
		<h2><?php echo $active_plugins[$configure]->info->name; ?> : <? echo $action; ?></h2>
		<?php
			Plugins::act( 'plugin_ui', $configure, $action );
		?>
	</div>
	<?php } ?>
</div>
<?php include( 'footer.php' ); ?>
