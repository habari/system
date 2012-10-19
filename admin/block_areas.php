<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<label><?php _e("Scope:"); ?> <select id="scope_id" onchange="themeManage.change_scope();">
	<option value="0"><?php _e('Default'); ?></option>
<?php foreach($scopes as $scope): ?>
	<option value="<?php echo $scope->id; ?>"
	<?php if (isset($scopeid) && $scopeid == $scope->id): ?>selected="selected"<?php endif; ?>><?php echo $scope->name; ?></option>
<?php endforeach; ?>
</select></label>

<div class="area_container">
<?php foreach ( $areas as $area => $area_name ): ?>
	<div class="area_drop_outer">
		<h2 data-areaname="<?php echo $area; ?>" title="<?php echo $area_name['description']; ?>"><?php echo $area_name['title']; ?></h2>
			<div class="area_drop" id="area_<?php echo $area; ?>">
			<?php if (isset($blocks_areas[$scopeid]) && is_array($blocks_areas[$scopeid]) && isset($blocks_areas[$scopeid][$area]) && is_array($blocks_areas[$scopeid][$area])): ?>
				<?php foreach($blocks_areas[$scopeid][$area] as $block): ?>
					<div class="block_drag block_instance_<?php echo $block->id; ?>">
						<h3><?php echo $block->title; ?></h3>
						<div class="close">&nbsp;</div>
						<div class="handle">&nbsp;</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
			<div class="no_blocks">
				<h3><?php _e( "No assigned blocks" ); ?></h3>
			</div>
		</div>
	</div>
<?php endforeach; ?>
</div>
