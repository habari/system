<label><?php _e("Scope:"); ?> <select id="scope_id">
	<option value="0"><?php _e('Default'); ?></option>
<?php foreach($scopes as $scope): ?>
	<option value="<?php echo $scope->id; ?>"
	<?php if($scope == $scope->id): ?>selected="selected"<?php endif; ?>><?php echo $scope->name; ?></option>
<?php endforeach; ?>
</select></label>
<div class="area_container">
<?php foreach ( $active_theme['info']->areas->area as $area ): ?>
<?php $scopeid = 0; ?>
	<div class="area_drop_outer">
		<h2><?php echo $area['name']; ?></h2>
			<div class="area_drop">
			<?php $area = (string)$area['name']; if(isset($blocks_areas[$scopeid]) && is_array($blocks_areas[$scopeid]) && isset($blocks_areas[$scopeid][$area]) && is_array($blocks_areas[$scopeid][$area])): ?>
			<?php foreach($blocks_areas[$scopeid][$area] as $block): ?>
				<div class="area_block"><h3 class="block_instance_<?php echo $block->id; ?>"><?php echo $block->title; ?><small><?php echo Utils::htmlspecialchars($block->type); ?></small></h3></div>
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
</div>
<div class="delete_drop"><span><?php echo _t('drag here to remove'); ?></span></div>
