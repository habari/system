<?php namespace Habari; ?>
<?php $theme->display('header'); ?>

		<?php echo $theme->area('top_content'); ?>

		<div id="posts" itemprop="blogPosts">
			<?php echo $theme->content($posts); ?>
		</div>

		<aside id="sidebar">
			<?php echo $theme->area('sidebar'); ?>
		</aside>

<?php $theme->display('footer'); ?>
