<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<div class="container main currenttheme">
	<h2 class="lead"><?php _e( 'Current Theme' ); ?></h2>
	<div class="item">
		<div class="head">
			<div class="title">
				<a href="<?php echo $active_theme['info']->url; ?>" class="plugin"><?php echo $active_theme['info']->name; ?></a> <?php echo $active_theme['info']->version; ?> <?php _e('by'); ?>
				<?php
				$authors = array();
				foreach ( $active_theme['info']->author as $author ) {
					$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
				}
				// @locale The string used between the last two items in the list of authors of a theme on the admin page (one, two, three *and* four).
				echo Format::and_list( $authors,  _t( ' and ' ));
				?>
			</div>
			<?php
				if ( isset( $active_theme['info']->help ) ):
					if( Controller::get_var('help') == $active_theme['dir'] ):
			?>
				<a class="help active" href="<?php URL::out( 'display_themes' ); ?>"><?php _e('Help'); ?></a>
				<?php else: ?>
				<a class="help" href="<?php URL::out( 'display_themes', 'help=' . $active_theme['dir'] ); ?>"><?php _e('Help'); ?></a>
				<?php
					endif;
				endif;
			?>

			<?php if ( $configurable ): ?>
			<ul class="dropbutton">
				<li><a href="<?php URL::out( 'display_themes', 'configure=' . $active_theme['dir'] ); ?>"><?php _e('Settings'); ?></a></li>
			</ul>
			<?php endif; ?>

			<?php if ( $active_theme['info']->update != '' ): ?>
			<ul class="dropbutton alert">
				<li><a href="#"><?php _e('v'); ?><?php echo $active_theme['info']->update; ?> <?php _e('Update Available'); ?></a></li>
			</ul>
			<?php endif; ?>

		</div>

		<div class="clearfix">
			<div class="thumb columns four"><img class="colmuns four" src="<?php echo $active_theme['screenshot']; ?>">
			</div>
			<div class="themeinfo columns ten">
				<p class="description"><?php echo $active_theme['info']->description; ?></p>
				<?php if ( $active_theme['info']->license != '' ): ?>
				<p class="description"><?php printf( _t('%1$s is licensed under the %2$s'), $active_theme['info']->name, '<a href="' . $active_theme['info']->license['url'] . '">' . $active_theme['info']->license . '</a>' ); ?></p>
				<?php endif; ?>

				<?php if ( isset( $active_theme['info']->help ) ): ?>
				<div id="themehelp" class="<?php if( Controller::get_var('help') == $active_theme['dir'] ): ?>active<?php endif; ?>">
					<div class="help">
						<?php echo Pluggable::get_xml_text($active_theme['info']['filename'], $active_theme['info']->help); ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

	<?php
	// Capture the admin config output.  If nothing is there, don't output the section
	ob_start();
	Plugins::act( 'theme_ui', $active_theme );
	$output = ob_get_clean();
	if (trim($output) != '') :
	?>

	<div class="item">
		<h3><?php _e( "General" ); ?></h3>
		<?php echo $output; ?>
		<div></div>
	</div>
	<?php endif; ?>

	<?php if ( isset($active_theme['info']->areas) ): ?>
	<div id="blocksconfigure" class="item container">
		<h3 class="lead"><?php _e( "Areas" ); ?></h3>
			<div id="block_add" class="columns ten">
				<?php $this->display('block_instances'); ?>
			</div>
			<div class="columns four">
				<div id="scope_container">
				<?php $this->display('block_areas'); ?>
				</div>
				<div class="formcontrol"><button id="save_areas" disabled="disabled"><?php _e('Save'); ?></button>
				</div>
			</div>
		<?php endif; ?>
	</div>

<div class="container main availablethemes">

	<h2 class="lead"><?php _e('Available Themes'); ?></h2>
<?php
foreach ( $all_themes as $inactive_theme ):
	if ( $inactive_theme['path'] != $active_theme_dir ) : ?>
	<div class="item <?php if ($previewed == $inactive_theme['dir']) echo " previewing"; ?>"> 
		<div class="head">
			<div class="title">
				<a href="<?php echo $inactive_theme['info']->url; ?>"><?php echo $inactive_theme['info']->name; ?> <?php echo $inactive_theme['info']->version; ?></a> <?php _e('by'); ?> 
				<?php
				$authors = array();
				foreach ( $inactive_theme['info']->author as $author ) {
					$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
				}
				// @locale The string used between the last two items in the list of authors of a theme on the admin page (one, two, three *and* four).
				echo Format::and_list( $authors,  _t( ' and ' ));
				?>
			</div>

		    <?php
		    if ( $inactive_theme['info']->getName() != 'pluggable' || (string) $inactive_theme['info']->attributes()->type != 'theme' ) : ?>
			<p class="legacy"><?php _e( 'Legacy theme.' ); ?></p>
	      <?php elseif(isset($inactive_theme['req_parent'])): ?>
			<p class="legacy"><?php _e( 'This theme requires the parent theme named "%s".', array($inactive_theme['req_parent']) ); ?></p>
		    <?php else: ?>
					<?php
					$dbtn = FormControlDropbutton::create('actions');
					$dbtn->append(FormControlSubmit::create('activate')->set_url(URL::get( 'activate_theme', 'theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ))->set_caption(_t('Activate')));
					if ($previewed == $inactive_theme['dir']) {
						$dbtn->append(FormControlSubmit::create('end_preview')->set_url(URL::get( 'preview_theme', 'theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ))->set_caption(_t('End Preview')));
					}
					else {
						$dbtn->append(FormControlSubmit::create('preview')->set_url(URL::get( 'preview_theme', 'theme_dir=' . $inactive_theme['dir'] . '&theme_name=' . $inactive_theme['info']->name ))->set_caption(_t('Preview')));
					}
					echo $dbtn->pre_out();
					echo $dbtn->get($theme);
					?>
		    <?php endif; ?>
		</div>
		<div class="clearfix">
		    <div class="thumb columns four">
				<img class="themethumb columns four" src="<?php echo $inactive_theme['screenshot']; ?>">
			</div>
			<div class="themeinfo columns ten">
				<div class="pointer"></div>
				<p class="description"><?php echo $inactive_theme['info']->description; ?></p>
				<?php if ( $inactive_theme['info']->license != '' ): ?>
				<p class="description"><?php printf( _t('%1$s is licensed under the %2$s'), $inactive_theme['info']->name, '<a href="' . $inactive_theme['info']->license['url'] . '">' . $inactive_theme['info']->license . '</a>' ); ?></p>
				<?php endif; ?>
			</div>
	</div>	
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
