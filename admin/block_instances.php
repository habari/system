<label><?php _e('New Block Name:')?> <input type="text" id="block_instance_title"></label>
<label><?php _e('Type:')?> <select id="block_instance_type">
	<?php foreach ( $blocks as $block_key => $block_name ): ?>
	<option value="<?php echo $block_key; ?>"><?php echo $block_name; ?></option>
	<?php endforeach; ?>
</select></label>
<input id="block_instance_add" type="button" value="+" >

<div id="block_instances">
	<?php foreach ( $block_instances as $instance ): ?>
	<div class="block_instance">
		<h3 class="block_instance_<?php echo $instance->id; ?>"><?php echo htmlspecialchars($instance->title, ENT_COMPAT, 'UTF-8'); ?></h3>
		<ul>
			<li><a href="#" onclick="var i = $('<iframe src=\'<?php echo URL::get('admin', array('page' => 'configure_block', 'blockid' => $instance->id)); ?>\' style=\'width:600px;height:300px;\'></iframe>'); i.dialog({bgiframe:true,height:300,width:778,modal:true,dialogClass:'jqueryui',draggable:false,title:'Configure Block: <?php echo htmlspecialchars($instance->title, ENT_COMPAT, 'UTF-8'); ?> (<?php echo htmlspecialchars($instance->type, ENT_COMPAT, 'UTF-8'); ?>)'});i.css('width','768px');return false;">configure</a></li>
			<li><a href="#" onclick="delete_block(<?php echo $instance->id; ?>);return false;">delete</a></li>
		</ul>
	</div>
	<?php endforeach; ?>
</div>
