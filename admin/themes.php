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
						<label><?php _e('New Block Name:')?> <input type="text" id="block_instance_name"></label>
						<label><?php _e('Type:')?> <select id="block_instance_type">
							<?php foreach ( $blocks as $block_key => $block_name ): ?>
							<option value="<?php echo $block_key; ?>"><?php echo $block_name; ?></option>
							<?php endforeach; ?>
						</select></label>
						<input id="block_instance_add" type="button" value="+" >

						<div id="block_instances">
							<?php foreach ( $block_instances as $instance ): ?>
							<div class="block_instance">
								<h3><a href="#"><?php echo htmlspecialchars($instance->title); ?></a></h3>
								<div>
									<?php $instance->get_form()->out(); ?>
								</div>
							</div>
							<?php endforeach; ?>
						</div>

					</div>

					<!--
					@todo: move this to the admin.js
					-->
					<script type="text/javascript">
					$(function(){
						$('#block_instance_add').click(function(){
							if($('#block_instance_name').val() == '') {
								alert('You must first name this instance.');
							}
							else {
								$('#block_instances').append($('<h3><a href="#">' + $('#block_instance_name').val() + '</a></h3><div>' + $('#block_instance_name').val() + '</div>'));
							}
						});

						$('#block_instances h3').click(function() {
							$(this).next().slideToggle('slow');
							return false;
						}).next().hide();


						$('.area_drop').sortable({placeholder: 'block_drop', forcePlaceholderSize: true, connectWith: '.area_drop', containment: '.area_container', axis: 'y'});
						$('.block_instance h3').draggable({connectToSortable: '.area_drop', helper: 'clone', distance: 5, containment: $('#block_instances h3').parents('.splitterinside')});
						/*
						$(".area_drop").droppable({
							connectToSortable: '#sortable',
							accept: '#block_instances h3',
							activeClass: 'drop_area_active',
							hoverClass: 'drop_area_hover',
							drop: function(event, ui) {
								//$(this).css({border: "1px solid red"});
							}
						});
						*/
						/*/
						$('#block_instances').sortable({placeholder: 'block_drop', forcePlaceholderSize: true, connectWith: '.area_drop', start: function(event, ui){
							$('h3', ui.item).next().hide();
						}});
						//*/

					});
					</script>

					<div id="scope_container">
					<label><?php _e("Scope:"); ?> <select><option>Default</option></select></label>
					<div class="area_container">
					<?php foreach ( $active_theme['info']->areas->area as $area ): ?>
						<h2><a href="#"><?php echo $area['name']; ?></a></h2>
						<div class="area_drop">
							<?php if ( is_array($blocks_areas[0][(string)$area['name']]) ): ?>
							<?php foreach ( $blocks_areas[0][(string)$area['name']] as $block ): ?>
								<div class="area_block"><h3><?php echo $block->title; ?></h3></div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					</div>
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
