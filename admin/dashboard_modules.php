<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<ul class="modules">
	<?php foreach($modules as $moduleid => $module): ?>
	<li class="module <?php echo Utils::slugify( $module['name'] ); ?>-module" id="<?php echo $moduleid . ':' . $module['name']; ?>">
	<?php if ( $module['name'] != _t('Add Item') ): ?>
		<div class="close">&nbsp;</div> 
	<?php endif; ?>
		<?php if ( $module['options'] ) : ?>
			<div class="options">&nbsp;</div>
		<?php endif; ?>
		
		<div class="modulecore">
			<h2><?php echo $module['title']; ?></h2>
			
			<?php if ( $moduleid != 'nosort' ) : ?>
				<div class="handle">&nbsp;</div>
			<?php endif; ?>
			
			<?php echo $module['content']; ?>
		</div>
		
		<?php if ( $module['options'] ) : ?>
		<div class="optionswindow">
			<h2><?php echo $module['title']; ?></h2>
			
			<?php if ( $moduleid != 'nosort' ) : ?>
				<div class="handle">&nbsp;</div>
			<?php endif; ?>
			
			<div class="optionscontent"> 
				<?php echo $module['options']; ?>
			</div>
		</div>
		<?php endif; ?>
	
	</li>
	<?php endforeach; ?>
</ul>
