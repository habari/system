<?php include('header.php');?>
<div class="container">
	<div class="column prepend-1 span-22 append-1">
		<h2>Currently Available Themes</h2>
		<p>Activate, deactivate and remove themes through this interface.</p>
		<div class="column prepend-1 span-22 append-1">
			<table cellspacing="0" width="100%">
				<thead>
					<tr>
						<th align="left">Name</th>
						<th align="left">Author</th>
						<th align="left">Version</th>
						<th align="left">Engine</th>
						<th align="left">Directory</th>
						<th align="center">Action</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$active_theme= Options::get('theme_dir');
				foreach( Themes::get_all() as $theme_dir => $theme_path ) :
					$info= simplexml_load_file( $theme_path . '/theme.xml' ); ?>
					<tr>
						<td><?php echo $info->name; ?></td>
						<td><a href="<?php echo $info->url; ?>"><?php echo $info->author; ?></a></td>
						<td><?php echo $info->version; ?></td>
						<td><?php echo $info->template_engine; ?></td>
						<td><?php echo $theme_dir; ?></td>
						<td align="center">
						<?php if ( $theme_dir != $active_theme ) { ?>
						<form method='post' action='<?php URL::out('admin', 'page=activate_theme'); ?>'>
						<input type='hidden' name='theme_name' value='<?php echo $info->name; ?>'>
						<input type='hidden' name='theme_dir' value='<?php echo $theme_dir; ?>'>
						<input type='submit' name='submit' value='activate'>
						</form>
						<?php } ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php include('footer.php');?>
