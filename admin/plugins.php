<?php include('header.php');?>
<div class="container">
<hr>
	<div class="dashboard-block span-24 first last" id="welcome">
		<h1>Currently Available Plugins</h1>
		<p>Activate, deactivate and remove plugins through this interface.</p>
	</div>
	<div class="dashboard-block span-24 first last">
		<?php 
			$listok= true;
			$all_plugins= Plugins::list_all();
			$active_plugins= Plugins::get_active();
			if(Plugins::changed_since_last_activation()) {
				$request= new RemoteRequest(URL::get('admin', array('page'=>'loadplugins')), 'POST', 300);
				$request->add_header(array('Cookie'=>$_SERVER['HTTP_COOKIE']));
				$result= $request->execute();
				if(!$request->executed() || preg_match('%^http/1\.\d 500%i', $request->get_response_headers())) {
					$listok= false;
		?>
			<p><?php _e('The plugin list has changed since the last activation.  Please wait while Habari loads the plugin list.'); ?></p>
		<?php
				}
			}
			Options::clear_cache();
			$failed_plugins= Options::get('failed_plugins');
			if(is_array($failed_plugins)) {
				$all_plugins= array_diff($all_plugins, $failed_plugins);
			} 
			if($listok) :
				if(count($failed_plugins) > 0) {
					echo '<div class="warning">';
					foreach($failed_plugins as $failed) {
						echo '<p>' . sprintf(_t('Attempted to load the plugin file "%s", but it failed with syntax errors.'), basename($failed)) . '</p>';
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
			foreach ( $all_plugins as $file ) :
				$verb= 'Activate';
				$plugin_id= Plugins::id_from_file( $file );
				if ( array_key_exists( $plugin_id, $active_plugins ) )
				{
					$verb= 'Deactivate';
					$plugin= $active_plugins[$plugin_id];
					$active= true;
				}
				else
				{
					// instantiate this plugin
					// in order to get its info()
					include_once($file);
					$plugin= Plugins::load($file);
					$active= false;
				}
				if(isset($plugin->info->url)) {
					$url = "<p><a href='{$plugin->info->url}' title='Visit {$plugin->info->name}'>{$plugin->info->author}</a></p>";
				}
				else {
					$url = $plugin->info->author;
				}
			?>
				<tr>
					<td><?php echo $plugin->info->name; ?> 
					</td>
					<td><?php echo $url; ?></td>
					<td><?php echo $plugin->info->version; ?></td>
					<td>
					<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
					<p><input type='hidden' name='plugin' value='<?php echo $file; ?>'></p>
					<p><input type='submit' name='submit' value='<?php echo $verb; ?>'></p>
					<?php 
					if ($active) {
						$actions= array();
						$actions= Plugins::filter('plugin_config', $actions, $plugin->plugin_id);
						foreach($actions as $action => $caption) {
							if(is_numeric($action)) {
								$action = $caption;
							}
							?>
							<p><a href="<?php URL::out( 'admin', array( 'page'=>'plugins', 'configure'=>$plugin->plugin_id, 'action'=>$action ) ); ?>"><?php echo $caption; ?></a></p>
							<?php
						}
					}
					?>
					</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<?php if( isset( $this->engine_vars['configure'] ) && ( $configure = $this->engine_vars['configure'] ) ): ?>
	<div class="dashboard-block span-24 first last" id="plugin_options">
		<h2><?php echo $active_plugins[$configure]->info->name; echo ' : '; echo $action; ?></h2>
		<?php
			Plugins::act('plugin_ui', $this->engine_vars['configure'], $this->engine_vars['action']);
		?>
	</div>
	<?php endif; ?>
</div>
<?php include('footer.php');?>
