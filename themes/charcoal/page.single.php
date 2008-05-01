<?php include 'header.php'; ?>

			<div id="main-posts">
				<div class="<?php echo $page_class?>">
				<?php if ( is_array( $post->tags ) ) : ?>
					<div class="post-tags">
						<?php echo $post->tags_out;?>
					</div>
				<?php endif ?>
					<div class="post-title">
						<h3>
							<a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a>
						</h3>
					</div>
					<div class="post-entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="post-footer">
					<?php if ( $user ) : ?>
						<span class="post-edit">
						<a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>" title="Edit post">Edit</a>
						</span>
					<?php endif;?>
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
		<?php if ( !$post->info->comments_disabled || $post->comments->moderated->count ) :?>
			<div id="post-comments">
			<?php 
				if ( $post->comments->moderated->count ) :
					foreach ( $post->comments->moderated as $comment ) :
						$class= '"post-comment';
						if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
							$class.= '-u';
						}
				$class.= '"';
			?>
				<div id="comment-<?php echo $comment->id; ?>" class=<?php echo $class; ?>>
					<div class="post-comment-commentor">
						<h2>
							<a href="<?php echo $comment->url; ?>" rel="external"><?php echo $comment->name; ?></a>
						</h2>
				</div>
				<div class="post-comment-body">
					<?php echo $comment->content_out; ?>
					<p class="post-comment-link"><a href="#comment-<?php echo $comment->id; ?>" title="Time of this comment"><?php echo $comment->date; ?></a></p>
				</div>
			</div>
			<?php endforeach; ?>
			<?php else : ?>
			<h2>Be the first to write a comment!</h2>
			<?php endif; ?>
			<div id="post-comments-footer">
				<!-- TODO: A hook can be placed here-->
			</div>
			</div>
		<?php endif; ?>
		<!-- comment form -->
		<?php include 'commentform.php'; ?>
		<!-- /comment form -->

<?php include 'footer.php'; ?>
