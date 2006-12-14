<?php
/**
* Test file for plugins display
*/

include_once( 'user/plugins/recent.php' );
foreach( $plugin::info as $plugin ) { 
$plugininfo = recent::info();
?>
<div id="content-area">
<h1>Currently Available Plugins</h1>
<table cellpadding="5" cellspacing="5">
	<tr>
		<th align="left">Plugin Name</th>
		<th align="left">Author Name</th>
		<th align="left">Copyright</th>
		<th>Action</th>
	</tr>
	<?php foreach( $plugin::info as $plugin ) { ?>
	<tr>
		<td><?php echo $plugininfo['name']; ?></td>
		<td><a href="<?php echo $plugininfo['link']; ?>" title="Visit <?php echo $plugininfo['name']; ?>"><?php echo $plugininfo['author']; ?></a></td>
		<td><?php echo $plugininfo['copyright']; ?></td>
		<td><a href="" title="">Enable</a></td>
	</tr>
	<?php } ?>
</table>
</div>