<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include 'header.php'; ?>

			<div id="main-posts">
				<p class="prompt"><?php echo $tags_msg; ?></p>
			<?php foreach ($posts as $post): ?>
				<div class="post multi">
				<?php if ( count( $post->tags ) && ($tags_in_multiple) ) : ?>
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
						<?php echo $post->content_excerpt; ?>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
		<div id="top-secondary"><?php include'sidebar.php' ?></div>
		<div class="clear"></div>
	</div>
</div>
<div id="page-bottom">
	<div id="wrapper-bottom">
		<div id="bottom-primary">
			<div id="prev-posts-footer">
				<span class="nav-next"><?php $theme->prev_page_link( _t('Newer Posts') ); ?></span>
				<span class="nav-prev"><?php $theme->next_page_link( _t('Older Posts') ); ?></span>
				<br class="clear">
			</div>
			<?php $theme->display_archives() ;?>

<?php include 'footer.php'; ?>
