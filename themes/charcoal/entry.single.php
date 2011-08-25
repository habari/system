<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include 'header.php'; ?>

			<div id="main-posts">
			<?php if ($show_post_nav) : ?>
				<div class="post-nav">
				<?php if ( $previous = $post->descend() ): ?>
					<div class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></div>
				<?php endif; ?>
				<?php if ( $next = $post->ascend() ): ?>
					<div class="right"><a href="<?php echo $next->permalink ?>" title="<?php echo $next->slug ?>"><?php echo $next->title ?></a> &raquo;</div>
				<?php endif; ?>
					<div class="clear"></div>
				</div>
			<?php endif; ?>
				<div class="<?php echo $post_class?>">
				<?php if ( is_array($post->tags) ) : ?>
					<div class="post-tags">
						<?php echo $post->tags_out;?>
					</div>
				<?php endif; ?>
					<div class="post-title">
						<h3>
							<a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a>
						</h3>
					</div>
					<div class="post-sup">
						<span class="post-date"><?php $post->pubdate->out(); ?></span>
						<span class="post-comments-link">
							<a href="<?php echo $post->permalink.'#comment-form'; ?>" title="<?php _e( "Comments on this post" ); ?>"><?php $theme->post_comments_link( $post ); ?></a>
						</span>
						<span class="clear"></span>
					</div>
					<div class="post-entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="post-footer">
					<?php if ( $loggedin ) : ?>
						<span class="post-edit">
							<a href="<?php echo $post->editlink; ?>" title="<?php _e( "Edit post" ); ?>"><?php _e( "Edit" ); ?></a>
						</span>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<div id="top-secondary">
			<?php include'sidebar.php' ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
<div id="page-bottom">
	<div id="wrapper-bottom">
		<div id="bottom-primary">
		
		<?php include 'comments.php'; ?>
		
		<!-- comment form -->
		
		<?php include 'commentform.php'; ?>
		
		<!-- /comment form -->
		
<?php include 'footer.php'; ?>
