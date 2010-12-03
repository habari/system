<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<label><?php _e('New Block Type:')?> <select id="block_instance_type">
	<?php foreach ( $blocks as $block_key => $block_name ): ?>
	<option value="<?php echo $block_key; ?>"><?php echo $block_name; ?></option>
	<?php endforeach; ?>
</select></label>
<label><?php _e('Name:')?> <input type="text" id="block_instance_title"></label>
<input id="block_instance_add" type="button" value="+" >

<div id="block_instances">
	<?php foreach ( $block_instances as $instance ): ?>
	<div class="block_instance">
		<h3 class="block_instance_<?php echo $instance->id; ?>"><?php echo Utils::htmlspecialchars($instance->title); ?><small><?php echo Utils::htmlspecialchars($instance->type); ?></small></h3>
		<ul>
		<?php if ( $instance->get_form()->controls ) { ?>
			<li><a href="#" onclick="var i = $('<iframe src=\'<?php echo URL::get('admin', array('page' => 'configure_block', 'blockid' => $instance->id)); ?>\' style=\'width:600px;height:300px;\'></iframe>'); i.dialog({bgiframe:true,height:300,width:778,modal:true,dialogClass:'jqueryui',draggable:false,title:'Configure Block: <?php echo Utils::htmlspecialchars($instance->title); ?> (<?php echo Utils::htmlspecialchars($instance->type); ?>)'});i.css('width','768px');return false;"><?php _e( 'Configure'); ?></a></li>
		<?php } ?>
			<li><a href="#" onclick="delete_block(<?php echo $instance->id; ?>);return false;"><?php _e('Delete'); ?></a></li>
		</ul>
	</div>
	<?php endforeach; ?>
</div>
