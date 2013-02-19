<?php $theme->display('header'); ?>

		<?php echo $theme->area('top_content'); ?>

		<div id="posts" itemprop="blogPosts">
			<?php echo $theme->content($posts); ?>
		</div>

		<?php echo $theme->area('sidebar'); ?>

<?php $theme->display('footer'); ?>
