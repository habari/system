<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $theme->display ( 'header' ); ?>
<!--begin content-->
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent" class="span-15 append-2">
			<!--begin single post navigation-->
			<div id="post-nav">
				<?php if ( $previous = $post->ascend() ): ?>
				<span class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></span>
				<?php endif; ?>
				<?php if ( $next = $post->descend() ): ?>
				<span class="right"><a href="<?php echo $next->permalink ?>" title="<?php echo $next->slug ?>"><?php echo $next->title ?></a> &raquo;</span>
				<?php endif; ?>
			</div>
			<!--begin loop-->
				<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
						<h2 class="prepend-2"><a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h2>
						<div class="cal"><?php echo $post->pubdate_out; ?></div>
						<div class="entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="entryMeta">
						<?php if ( count( $post->tags ) ) { ?>
						<div class="tags"><?php _e('Tagged:'); ?> <?php echo $post->tags_out; ?></div>
						<?php } ?>
					</div><br>
						<?php if ( $loggedin ) { ?>
						<a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit post'); ?>"><?php _e('Edit'); ?></a>
						<?php } ?>

				</div>

			<!--end loop-->
			<?php include 'commentform.php'; ?>
			</div>
		<!--end primary content-->
		<?php $theme->display ( 'sidebar' ); ?>
	</div>
	<!--end content-->
	<?php $theme->display ( 'footer' ); ?>
