<?php namespace Habari; ?>
<?php $theme->display('header'); ?>

	<?php echo $theme->area('top_content'); ?>

	<div id="blank">
		<?php echo $theme->content($content); ?>
	</div>

	<?php if(!isset($sidebar) || $sidebar == true): ?>
	<aside id="sidebar">
		<?php echo $theme->area('sidebar'); ?>
	</aside>
	<?php endif; ?>

<?php $theme->display('footer'); ?>
