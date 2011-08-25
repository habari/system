<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include 'header.php'; ?>

			<div id="main-posts">
            <?php $posts = (array) $posts; ?>
			<?php if ( sizeof( $posts ) ): ?>
				<?php $post =reset($posts); ?>
				<div class="<?php echo $post_class?>">
				<?php if ( count( $post->tags ) ) : ?>
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
						<span class="post-date">
							<?php $post->pubdate->out(); ?>
						</span>
						<span class="post-comments-link">
							<a href="<?php echo $post->permalink.'#comment-form'; ?>" title="<?php _e( "Comments on this post" ); ?>"><?php $theme->post_comments_link( $post ); ?></a>
						</span>
						<br class="clear">
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
			<?php else: ?>
				<p class="noposts prompt"><?php _e( "No posts published, yet." ); ?></p>
			<?php endif; ?>
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
			<div id="prev-posts">
			<?php while ($post =next($posts)) : ?>
				<div class="prev-post">
					<div class="prev-post-title">
						<h2>
							<a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a>
						</h2>
					</div>
					<div class="prev-post-excerpt">
						<p>
							<?php echo $post->content_excerpt; ?>
							<a href="<?php echo $post->permalink; ?>" title="<?php printf( _t("Continue reading %s"), $post->title ); ?>"><img src="<?php Site::out_url( 'theme' ); ?>/images/arrow.png" alt="<?php _e( "more" ); ?>"></a>
						</p>
					</div>
				</div>
			<?php endwhile; ?>
			</div>
			<div id="prev-posts-footer">
				<span class="nav-next"><?php $theme->prev_page_link( _t('Newer Posts') ); ?></span>
				<span class="nav-prev"><?php $theme->next_page_link( _t('Older Posts') ); ?></span>
				<br class="clear">
			</div>
			<?php //$theme->prevnext($page, Utils::archive_pages($posts->count_all())); ?>
			<?php $theme->display_archives() ;?>
			
<?php include 'footer.php'; ?>
