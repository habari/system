<?php include('header.php');?>
<div id="content-area">
	<div class="dashbox c3" id="welcome">
		<h1>Currently Available Plugins</h1>
		<p>Activate, deactivate and remove plugins through this interface.</p>
		<div class="dashbox c3">
			<table cellspacing="0" width="100%">
				<thead>
					<tr>
						<th align="left">Plugin Name</th>
						<th align="left">Author Name</th>
						<th align="left">License</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach( Plugins::get_active() as $plugin ) : ?>
				<?php $plugininfo = $plugin->info(); ?>
					<tr>
						<td><?php echo $plugininfo['name']; ?></td>
						<td><a href="<?php echo $plugininfo['link']; ?>" title="Visit <?php echo $plugininfo['name']; ?>"><?php echo $plugininfo['author']; ?></a></td>
						<td><?php echo $plugininfo['license']; ?></td>
						<td><a href="" title="">Enable</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php include('footer.php');?>
