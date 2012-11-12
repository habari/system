<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<div class="container currenttheme">
	<h2><?php _e( 'Current Theme' ); ?></h2>
	<div class="item clear">
		<div class="head">

			<a href="<?php echo $active_theme['info']->url; ?>" class="plugin"><?php echo $active_theme['info']->name; ?></a> <span class="version dim"><?php echo $active_theme['info']->version; ?></span> <span class="dim"><?php _e('by'); ?></span>
			<?php
			$authors = array();
			foreach ( $active_theme['info']->author as $author ) {
				$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
			}
			// @locale The string used between the last two items in the list of authors of a theme on the admin page (one, two, three *and* four).
			echo Format::and_list( $authors, '<span class="dim">, </span>', '<span class="dim">' . _t( ' and ' ) . '</span>');
			?>
			<?php
				if ( isset( $active_theme['info']->help ) ):
					if( Controller::get_var('help') == $active_theme['dir'] ):
			?>
				<a class="help active" href="<?php URL::out( 'admin', 'page=themes' ); ?>"><?php _e('Help'); ?></a>
				<?php else: ?>
				<a class="help" href="<?php URL::out( 'admin', 'page=themes&help=' . $active_theme['dir'] ); ?>"><?php _e('Help'); ?></a>
				<?php
					endif;
				endif;
			?>

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

			<?php if ( isset( $active_theme['info']->help ) ): ?>
			<div id="themehelp" class="pct70 <?php if( Controller::get_var('help') == $active_theme['dir'] ): ?>active<?php endif; ?>">
				<div class="help">
					<?php echo (string) $active_theme['info']->help->value; ?>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</div>

	<?php
	// Capture the admin config output.  If nothing is there, don't output the section
	ob_start();
	Plugins::act( 'theme_ui', $active_theme );
	$output = ob_get_clean();
	if (trim($output) != '') :
	?>

	<div class="item clear">
		<h3><?php _e( "General" ); ?></h3>
		<?php echo $output; ?>
		<div></div>
	</div>
	<?php endif; ?>

	<?php if ( isset($active_theme['info']->areas) ): ?>
	<div id="blocksconfigure" class="item clear">
		<h3><?php _e( "Areas" ); ?></h3>
				<div>
					<div id="block_add">
						<?php $this->display('block_instances'); ?>
					</div>

					<div id="scope_container">
					<?php $this->display('block_areas'); ?>
					</div>
					<hr style="clear:both;visibility: hidden;height:5px;" />
					<div class="formcontrol"><button id="save_areas" disabled="disabled"><?php _e('Save'); ?></button>
					</div>
					<hr style="clear:both;visibility: hidden;" />
				</div>
			</div>
			<?php endif; ?>

<?php /* hide this until Scope is implemented
		<div class="item clear">
		<h3>Scopes</h3>


			<div id="tab_config_scopes" class="zsplitter">
				<div class="splitterinside">Scope config goes here</div>
			</div>

		</div>
*/ ?>
	</div>

<div class="container availablethemes">

	<h2><?php _e('Available Themes'); ?></h2>
<?php
foreach ( $all_themes as $inactive_theme ):
	if ( $inactive_theme['path'] != $active_theme_dir ) : ?>
	<div class="item pct30<?php if ($previewed == $inactive_theme['dir']) echo " previewing"; ?>"> 
		<div class="head theme_credits"> 
			<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <span class="version dim"><?php echo $inactive_theme['info']->version; ?></span></a> <span class="dim"><?php _e('by'); ?></span> 
			<?php
			$authors = array();
			foreach ( $inactive_theme['info']->author as $author ) {
				$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
			}
			// @locale The string used between the last two items in the list of authors of a theme on the admin page (one, two, three *and* four).
			echo Format::and_list( $authors, '<span class="dim">, </span>', '<span class="dim">' . _t( ' and ' ) . '</span>');
			?>
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
	    <?php
	    if ( $inactive_theme['info']->getName() != 'pluggable' || (string) $inactive_theme['info']->attributes()->type != 'theme' ) : ?>
		<p class="legacy"><?php _e( 'Legacy theme.' ); ?></p>
      <?php elseif(isset($inactive_theme['req_parent'])): ?>
		<p class="legacy"><?php _e( 'This theme requires the parent theme named "%s".', array($inactive_theme['req_parent']) ); ?></p>
	    <?php else: ?>
		<ul class="dropbutton"> 
			<?php if ($previewed == $inactive_theme['dir']): ?>
			<li><a href="<?php URL::out( 'admin', 'page=preview_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('End Preview'); ?></a></li>
			<?php else: ?>
			<li><a href="<?php URL::out( 'admin', 'page=preview_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Preview'); ?></a></li>
			<?php endif; ?>
			<li><a href="<?php URL::out( 'admin', 'page=activate_theme&theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ); ?>"><?php _e('Activate'); ?></a></li>
		</ul>
	    <?php endif; ?>
	</div> 	

<?php
	endif;
endforeach;
?>
</div>

<?php if (isset($theme_loader) && $theme_loader != ''): ?>
<div class="container" id="themeloader">
<?php echo $theme_loader; ?>
</div>
<?php endif; ?>

<?php include('footer.php');?>
