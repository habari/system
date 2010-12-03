<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $theme->display ( 'header' ); ?>
<!--begin content-->
	<div id="page">
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent" class="span-16 append-1">
			<!--begin loop-->

				<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
						<h2><a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h2>
					<div class="entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="entryMeta">
						<?php if ( $loggedin ) { ?>
						<a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit post'); ?>"><?php _e('Edit'); ?></a>
						<?php } ?>
					</div>
				</div>

			<!--end loop-->
			</div>
		<!--end primary content-->
		<?php $theme->display ( 'sidebar' ); ?>
	</div>
	</div>
	<!--end content-->
	<?php $theme->display ( 'footer' ); ?>
