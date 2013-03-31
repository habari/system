<?php namespace Habari; ?>
<?php $theme->display('header'); ?>

	<?php echo $theme->area('top_content'); ?>

	<div id="error">
		<p>The page you have requested is not available.</p>
	</div>

	<?php if(!isset($sidebar) || $sidebar == true): ?>
	<aside id="sidebar">
		<?php echo $theme->area('sidebar'); ?>
	</aside>
	<?php endif; ?>

<?php $theme->display('footer'); ?>
