<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $theme->display('header'); ?>

<div class="container main plugins">
<?php $facet = FormControlFacet::create('search')
	->set_property('data-facet-config', array(
			// #tag_collection is the object the manager function works on - the corresponding AJAX function will replace its content
		'onsearch' => '$("plugin_collection").manager("quicksearch", self.data("visualsearch").searchQuery.facets());',
		'facetsURL' => URL::get('admin_ajax_tag_facets', array('context' => 'tag_facets', 'component' => 'facets')),
		'valuesURL' => URL::get('admin_ajax_tag_facets', array('context' => 'tag_facets', 'component' => 'values')),
	));
echo $facet->pre_out();
echo $facet->get($theme); ?>
</div>

<div id="plugin_collection">
<?php
if ( isset($config_plugin) ): ?>
<div class="container main plugins configureplugin" id="configureplugin">
	<h2><?php echo $config_plugin['info']->name; ?> &middot; <?php echo $config_plugin_caption; ?></h2>
	<?php
	$theme->config = true;
	$theme->plugin = $config_plugin;
	$theme->display('plugin');
	$theme->config = false;
	?>
</div>
<?php endif; ?>

<?php if ( count($active_plugins) > 0 ): ?>
<div class="container main plugins activeplugins" id="activeplugins">
	<h2 class="lead"><?php _e('Active Plugins'); ?></h2>
	<?php
	foreach ( $active_plugins as $plugin ) {
		$theme->plugin = $plugin;
		$theme->display('plugin');
	}
	?>
</div>
<?php endif; ?>

<?php if ( count($inactive_plugins) > 0 ): ?>
<div class="container main plugins inactiveplugins" id="inactiveplugins">

	<h2 class="lead"><?php _e('Inactive Plugins'); ?></h2>
	
	<?php
	foreach ( $inactive_plugins as $plugin) {
		$theme->plugin = $plugin;
		$theme->display('plugin');
	}
	?>

</div>
<?php endif; ?>
</div>

<?php if ( isset($plugin_loader) && $plugin_loader != '' ): ?>
<?php echo $plugin_loader; ?>
<?php endif; ?>

<!--<div class="container uploadpackage">
	<h2><?php _e('Upload Plugin Package'); ?></h2>
	<div class="uploadform">
		<input type="file">
		<input type="submit" value="<?php _e('Upload'); ?>">
	</div>
</div>-->

<script type="text/javascript">
$('#plugin_collection').manager();
</script>

<?php $theme->display('footer'); ?>
