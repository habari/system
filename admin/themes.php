<?php include('header.php');?>


<div class="container currenttheme">
	<h2><?php _e('Current Theme'); ?></h2>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $active_theme['info']->url; ?>" class="plugin"><?php echo $active_theme['info']->name; ?></a> <span class="version dim"><?php echo $active_theme['info']->version; ?></span> <span class="dim"><?php _e('by'); ?></span> <a href="<?php echo $active_theme['info']->url; ?>"><?php echo $active_theme['info']->author; ?></a>

			<?php if ( $configurable ): ?>
			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'admin', 'page=themes&configure=' . $active_theme['dir'] ); ?>"><?php _e('Settings'); ?></a></li>
			</ul>
			<?php endif; ?>

			<?php if ( $active_theme['info']->update != '' ): ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v'); ?><?php echo $active_theme['info']->update; ?> <?php _e('Update Available'); ?></a></li>
			</ul>
			<?php endif; ?>
		</div>

		<div>
			<div class="thumb pct30"><span><img src="<?php echo $active_theme['screenshot']; ?>"></span></div>

			<p class="description pct70"><?php echo $active_theme['info']->description; ?></p>
			<?php if ( $active_theme['info']->license != '' ): ?>
			<p class="description pct70"><?php printf( _t('%1$s is licensed under the %2$s'), $active_theme['info']->name, '<a href="' . $active_theme['info']->license['url'] . '">' . $active_theme['info']->license . '</a>' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="pagesplitter">
			<ul class="tabcontrol tabs">
				<li><a href="#tab_config_general"><?php _e('General'); ?></a></li><?php if(isset($active_theme['info']->areas)): ?><li><a href="#tab_config_areas"><?php _e('Areas'); ?></a></li><?php endif; ?><li><a href="#tab_config_scopes"><?php _e('Scopes'); ?></a></li>
			</ul>

			<div id="tab_config_general" class="splitter">
				<div class="splitterinside"><?php Plugins::act( 'theme_ui', $active_theme ); ?></div>
			</div>
			<?php if ( isset($active_theme['info']->areas) ): ?>
			<div id="tab_config_areas" class="splitter">
				<div class="splitterinside">
					<div id="block_add">
						<?php $this->display('block_instances'); ?>

					</div>

					<!--
					@todo: move this to the admin.js
					-->
					<script type="text/javascript">
					function reset_block_form() {
						$('#block_instance_add').click(function(){
							$('#block_add').load(
								habari.url.ajaxAddBlock, 
								{title:$('#block_instance_title').val(), type:$('#block_instance_type').val()},
								reset_block_form
							);
						});

						$('.block_instance h3').draggable({
							connectToSortable: '.area_drop', 
							helper: 'clone', 
							distance: 5, 
							containment: $('#block_instances h3').parents('.splitterinside'),
							start: function(){$('.area_drop').sortable('refresh');}
						});
						$('.area_drop').sortable({placeholder: 'block_drop', forcePlaceholderSize: true, connectWith: '.area_drop,.delete_drop', containment: $('#block_add').parents('.splitterinside')});
					}
					function delete_block(id){
						$('#block_add').load(
							habari.url.ajaxDeleteBlock, 
							{block_id:id},
							reset_block_form
						);
					}
					$(function(){
						$('.delete_drop').sortable({
							over: function(event, ui){$(this).css('border', '1px dotted red');},
							out: function(event, ui){$(this).css('border', null);},
							receive: function(event, ui){
								$(ui.item).remove();
							}
						});
						reset_block_form();
					});
					function save_areas(){
						var output = {};
						$('.area_drop_outer').each(function(){
							var area = $('h2', this).text();
							output[area] = [];
							$('h3', this).each(function(){
								m = $(this).attr('class').match(/block_instance_(\d+)/)
								output[area].push(m[1]);
							});
						});
						$('#scope_container').load(
							habari.url.ajaxSaveAreas, 
							{area_blocks:output, scope:$('#scope_id').val()},
							reset_block_form
						);
					}
					</script>

					<div id="scope_container">
					<label><?php _e("Scope:"); ?> <select id="scope_id"><option value="0">Default</option></select></label>
					<div class="area_container">
					<?php foreach ( $active_theme['info']->areas->area as $area ): ?>
					<?php $scopeid = 0; ?>
						<div class="area_drop_outer">
							<h2><?php echo $area['name']; ?></h2>
								<div class="area_drop">
								<?php if(isset($blocks_areas[$scopeid]) && is_array($blocks_areas[$scopeid]) && is_array($blocks_areas[$scopeid][(string)$area['name']])): ?>
								<?php foreach($blocks_areas[$scopeid][(string)$area['name']] as $block): ?>
									<div class="area_block"><h3 class="block_instance_<?php echo $instance->id; ?>"><?php echo $block->title; ?></h3></div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
					</div>
					<div class="delete_drop"><span><?php echo _t('drag here to remove'); ?></span></div>
					</div>
					<hr style="clear:both;visibility: hidden;height:5px;" />
					<div id="save_areas" class="formcontrol"><button onclick="save_areas();return false;"><?php _e('Save'); ?></button>
					</div>
					<hr style="clear:both;visibility: hidden;" />
				</div>
			</div>
			<?php endif; ?>

			<div id="tab_config_scopes" class="splitter">
				<div class="splitterinside">Scopes</div>
			</div>


		</div>
	</div>

</div>

<div class="container availablethemes">

	<h2><?php _e('Available Themes'); ?></h2>
<?php
foreach ( $all_themes as $inactive_theme ):
	if ( $inactive_theme['path'] != $active_theme_dir ) : ?>
	<div class="item clear">
		<div class="head">
			<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <span class="version dim"><?php echo $inactive_theme['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <a href="<?php echo $inactive_theme['info']->url; ?>" class="author"><?php echo $inactive_theme['info']->author; ?></a>

			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'admin', 'page=activate_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Activate'); ?></a></li>
			</ul>
		</div>

		<div>
			<div class="thumb pct30"><span><img src="<?php echo $inactive_theme['screenshot']; ?>"></span></div>

			<p class="description pct70"><?php echo $inactive_theme['info']->description; ?></p>
			<?php if ( $inactive_theme['info']->license != '' ): ?>
			<p class="description pct70"><?php printf( _t('%1$s is licensed under the %2$s'), $inactive_theme['info']->name, '<a href="' . $inactive_theme['info']->license['url'] . '">' . $inactive_theme['info']->license . '</a>' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

<?php
	endif;
endforeach;
?>
</div>

<?php include('footer.php');?>
