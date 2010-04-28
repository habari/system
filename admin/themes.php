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
	</div>
	
	<?php
	// Capture the admin config output.  If nothing is there, don't output the section
	ob_start();
	Plugins::act( 'theme_ui', $active_theme );
	$output = ob_get_clean();
	if(trim($output) != '') :
	?>
	
	<div class="item clear">
		<h3>General</h3>
		<?php echo $output; ?>
		<div></div>
	</div>
	<?php endif; ?>

	<?php if ( isset($active_theme['info']->areas) ): ?>
	<div class="item clear">
		<h3>Areas</h3>
				<div>
					<div id="block_add">
						<?php $this->display('block_instances'); ?>
					</div>

					<!--
					@todo: move this to the admin.js
					-->
					<script type="text/javascript">
					function reset_block_form() {
						$('#block_instance_add').unbind('click').click(function(){
							spinner.start();
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
							containment: $('#block_instances h3').parents('.item'),
							start: function(){$('.area_drop').sortable('refresh');}
						});
						$('.area_drop').sortable({placeholder: 'block_drop', forcePlaceholderSize: true, connectWith: '.area_drop,.delete_drop', containment: $('#block_add').parents('.item')});
					}
					function delete_block(id){
						spinner.start();
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
						spinner.start();
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
					<?php $this->display('block_areas'); ?>
					</div>
					<hr style="clear:both;visibility: hidden;height:5px;" />
					<div id="save_areas" class="formcontrol"><button onclick="save_areas();return false;"><?php _e('Save'); ?></button>
					</div>
					<hr style="clear:both;visibility: hidden;" />
				</div>
			</div>
			<?php endif; ?>

		<div class="item clear">
		<h3>Scopes</h3>

			<div id="tab_config_scopes" class="zsplitter">
				<div class="splitterinside">Scope config goes here</div>
			</div>


		</div>
	</div>

<div class="container availablethemes">

	<h2><?php _e('Available Themes'); ?></h2>
<?php
foreach ( $all_themes as $inactive_theme ):
	if ( $inactive_theme['path'] != $active_theme_dir ) : ?>
	
	<div class="item pct30<?php if($previewed == $inactive_theme['dir']) echo " previewing"; ?>"> 
		<div class="head theme_credits"> 
			<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <span class="version dim"><?php echo $inactive_theme['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> <a href="<?php echo $inactive_theme['info']->url; ?>" class="author"><?php echo $inactive_theme['info']->author; ?></a>
		</div> 
 
		<div class="thumb">
			<img src="<?php echo $inactive_theme['screenshot']; ?>" class="themethumb">
			<div class="themeinfo">
				<div class="pointer"></div>
				<p class="description"><?php echo $inactive_theme['info']->description; ?></p>
				<?php if ( $inactive_theme['info']->license != '' ): ?>
				<p class="description"><?php printf( _t('%1$s is licensed under the %2$s'), $inactive_theme['info']->name, '<a href="' . $inactive_theme['info']->license['url'] . '">' . $inactive_theme['info']->license . '</a>' ); ?></p>
				<?php endif; ?>
			</div>
		</div> 
		<ul class="dropbutton"> 
			<?php if($previewed == $inactive_theme['dir']): ?>
			<li><a href="<?php URL::out( 'admin', 'page=preview_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('End Preview'); ?></a></li>
			<?php else: ?>
			<li><a href="<?php URL::out( 'admin', 'page=preview_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Preview'); ?></a></li>
			<?php endif; ?>
			<li><a href="<?php URL::out( 'admin', 'page=activate_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Activate'); ?></a></li>
		</ul> 
	</div> 	

<?php
	endif;
endforeach;
?>
</div>

<?php if(isset($theme_loader) && $theme_loader != ''): ?>
<div class="container" id="themeloader">
<?php echo $theme_loader; ?>
</div>
<?php endif; ?>

<?php include('footer.php');?>
