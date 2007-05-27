<?php include('header.php');?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Currently Available Plugins</h1>
		<p>Activate, deactivate and remove plugins through this interface.</p>
		<div class="dashboard-block c3">
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
					if ( array_key_exists( $file, $active_plugins ) )
					{
						$verb= 'Deactivate';
						$info= $active_plugins[$file]->info();
					}
					else
					{
						// instantiate this plugin
						// in order to get its info()
						include_once($file);
						$plugin= Plugins::load($file);
						$info= $plugin->info();
					}
				?>
					<tr>
						<td><?php echo $info['name']; ?></td>
						<td><a href='<?php echo $info['url']; ?>' title='Visit <?php echo $info['name']; ?>'><?php echo $info['author']; ?></a></td>
						<td><?php echo $info['version']; ?></td>
						<td>
						<form method='POST' action='<?php URL::out( 'admin', 'page=plugin_toggle' ); ?>'>
						<input type='hidden' name='plugin' value='<?php echo $file; ?>' />
						<input type='submit' name='submit' value='<?php echo $verb; ?>' />
						</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php include('footer.php');?>
