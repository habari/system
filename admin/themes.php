<?php include('header.php');?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Currently Available Themes</h1>
		<p>Activate, deactivate and remove themes through this interface.</p>
		<div class="dashboard-block c3">
			<table cellspacing="0" width="100%">
				<thead>
					<tr>
						<th align="center">Action</th>
						<th align="left">Name</th>
            <th align="left">Version</th>
						<th align="left">Engine</th>
						<th align="left">Directory</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$active_theme= Options::get('theme_dir');
				foreach( Themes::get_all() as $theme_dir ) :
					$info= simplexml_load_file( $theme_dir . '/theme.xml' ); ?>
					<tr>
						<td>
						<?php
							if ( $theme_dir != $active_theme )
							{ ?>
							<form method='post' action='<?php URL::out('admin', 'page=activate_theme'); ?>' />
							<input type='hidden' name='theme_name' value='<?php echo $info->name; ?>' />
							<input type='hidden' name='theme_dir' value='<?php echo $theme_dir; ?>' />
							<input type='submit' name='submit' value='activate' />
							</form>
							<?php }
						?>
						</td>
						<td><?php echo $info->name; ?></td>
						<td><?php echo $info->version; ?></td>
						<td><?php echo $info->template_engine; ?></td>
						<td><?php echo $theme_dir; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php include('footer.php');?>
