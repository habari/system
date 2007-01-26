<?php include('header.php');?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Currently Available Themes</h1>
		<p>Activate, deactivate and remove themes through this interface.</p>
		<div class="dashboard-block c3">
			<table cellspacing="0" width="100%">
				<thead>
					<tr>
						<th align="center">Active?</th>
						<th align="left">Theme Name</th>
            <th align="left">Version</th>
						<th align="left">Template Engine</th>
						<th align="left">Theme Directory</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach( Themes::get_all() as $theme ) : ?>
					<tr>
						<td><?php echo (int) $theme->is_active == 1 ? 'Yes' : 'No'; ?></td>
						<td><?php echo $theme->name; ?></td>
						<td><?php echo $theme->version; ?></td>
						<td><?php echo $theme->template_engine; ?></td>
						<td><?php echo $theme->theme_dir; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php include('footer.php');?>
