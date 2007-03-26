<?php include('header.php');?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Currently Available Plugins</h1>
		<p>Activate, deactivate and remove plugins through this interface.</p>
		<div class="dashboard-block c3">
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
				$active_plugins= Plugins::get_active();
				foreach ( Plugins::list_all() as $file ) :
					$verb= 'Activate';
					$class= Plugins::class_from_filename( $file );
					if ( array_key_exists( $file, $active_plugins ) )
					{
						$verb= 'Deactivate';
						$info= $active_plugins[$file]->info();
					}
					else
					{
						// instantiate this plugin
						// in order to get its info()
						include_once( $file );
						$plugin= new $class;
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
		</div>
	</div>
</div>
<?php include('footer.php');?>
