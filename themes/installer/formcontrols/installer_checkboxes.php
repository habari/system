<?php foreach($options as $plugin) { ?>
<div class="item clear">
	<div class="head">
		<span class="checkbox"><input type="checkbox" name="plugin_<?php echo $plugin['plugin_id']; ?>" id="plugin_<?php echo $plugin['plugin_id']; ?>"<?php if ($plugin['recommended']) echo ' checked'; ?>></span><label for="plugin_<?php echo $plugin['plugin_id']; ?>" class="name"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></label>
	</div>

	<div class="help"><?php echo $plugin['info']->description; ?></div>

</div>
<?php } ?>

<div class="controls item">
	<span class="checkbox"><input type="checkbox" name="checkbox_controller" id="checkbox_controller"></span>
	<label for="checkbox_controller">None Selected</label>
</div>