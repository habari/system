<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<label><?php _e("Scope:"); ?> <select id="scope_id" onchange="change_scope();">
	<option value="0"><?php _e('Default'); ?></option>
<?php foreach($scopes as $scope): ?>
	<option value="<?php echo $scope->id; ?>"
	<?php if (isset($scopeid) && $scopeid == $scope->id): ?>selected="selected"<?php endif; ?>><?php echo $scope->name; ?></option>
<?php endforeach; ?>
</select></label>

<div class="area_container">
<?php foreach ( $active_theme['info']->areas->area as $area ): ?>

	<div class="area_drop_outer">
		<h2><?php echo $area['name']; ?></h2>
			<div class="area_drop">
			<?php $area = (string)$area['name']; if (isset($blocks_areas[$scopeid]) && is_array($blocks_areas[$scopeid]) && isset($blocks_areas[$scopeid][$area]) && is_array($blocks_areas[$scopeid][$area])): ?>
			<?php foreach($blocks_areas[$scopeid][$area] as $block): ?>
				
				<div class="block_drag block_instance_<?php echo $block->id; ?>">
					<h3><?php echo $block->title; ?><small><?php echo Utils::htmlspecialchars($block->type); ?></small></h3>
					<ul class="instance_controls dropbutton">
						<li class="first-child"><a href="#" onclick="var i = $('&lt;iframe src=\'http://habari.vm/admin/configure_block?blockid=41\' style=\'width:600px;height:300px;\'&gt;&lt;/iframe&gt;'); i.dialog({bgiframe:true,width:778,height:270,modal:true,dialogClass:'jqueryui',title:'Configure Block: Posts (grayposts)'});i.css('width','768px');return false;">Configure</a></li>
						<li class="last-child"><a href="#" onclick="delete_block(41);return false;">Delete</a></li>
					</ul>
				</div>
						
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
</div>
<div class="delete_drop"><span><?php echo _t('drag here to remove'); ?></span></div>
