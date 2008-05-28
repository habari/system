<?php include 'header.php'; ?>

			<div id="main-posts">
			<?php if ($show_post_nav) : ?>
				<div class="post-nav">
				<?php if ( $previous= $post->descend() ): ?>
					<div class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></div>
				<?php endif; ?>
				<?php if ( $next= $post->ascend() ): ?>
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
						<span class="post-date"><?php echo $post->pubdate_out; ?></span>
						<span class="post-comments-link">
							<a href="<?php echo $post->permalink.'#comment-form'; ?>" title="<?php _e( "Comments on this post" ); ?>"><?php $theme->post_comments_link( $post, _t('No Comments'), _t('%s Comment'), _t('%s Comments') ); ?></a>
						</span>
						<span class="clear"></span>
					</div>
					<div class="post-entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="post-footer">
					<?php if ( $user ) : ?>
						<span class="post-edit">
							<a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>"title="<?php _e( "Edit post" ); ?>"><?php _e( "Edit" ); ?></a>
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
						<p class="post-comment-link"><a href="#comment-<?php echo $comment->id; ?>" title="Time of this comment - Click for comment permalink"><?php echo $comment->date; ?></a></p>
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
