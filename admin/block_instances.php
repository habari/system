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
		<div class="block_drag block_instance_<?php echo $instance->id; ?>">
			<h3><?php echo Utils::htmlspecialchars($instance->title); ?><small><?php echo Utils::htmlspecialchars($instance->type); ?></small></h3>
			<ul class="instance_controls dropbutton">
				<li><a href="#" onclick="var i = $('<iframe src=\'<?php echo URL::get('admin', array('page' => 'configure_block', 'blockid' => $instance->id)); ?>\' style=\'width:768px;height:400px;\'></iframe>'); i.dialog({bgiframe:true,width:778,height:400,modal:true,dialogClass:'jqueryui',title:'<?php _e('Configure Block: %1s (%2s)', array(Utils::htmlspecialchars($instance->title), Utils::htmlspecialchars($instance->type))); ?>',close:function(event,ui){$(this).dialog('destroy')}});i.css('width','768px').parent('div').css({left:$(window).width()/2-384});return false;"><?php _e( 'Configure'); ?></a></li>
				<?php if ( count($active_theme['info']->areas) > 0 ): ?>
					<?php foreach ( $active_theme['info']->areas->area as $area ): ?>
						<li class="area_available target_area_<?php echo $area['name']; ?>"><a href="#">Add to <?php echo $area['name']; ?></a></li>
					<?php endforeach; ?>
				<?php endif; ?>


				<li><a href="#" onclick="themeManage.delete_block(<?php echo $instance->id; ?>);return false;"><?php _e('Delete'); ?></a></li>
			</ul>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<script type="text/javascript">$(function(){findChildren();});</script>
