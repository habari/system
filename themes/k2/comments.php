<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!-- comments -->
		<hr>

		<div class="comments">
		<h4><span id="comments"><?php echo $post->comments->moderated->count; ?> <?php _e('Responses to'); ?> <?php echo $post->title; ?></span></h4>
		<div class="metalinks">
			<span class="commentsrsslink"><a href="<?php echo $post->comment_feed_link; ?>"><?php _e('Feed for this Entry'); ?></a></span>
		</div>

		<ol id="commentlist">
<?php
if ( $post->comments->moderated->count ) {
	foreach ( $post->comments->moderated as $comment ) {

		if ( $comment->url_out == '' ) {
			$comment_url = $comment->name_out;
		}
		else {
			$comment_url = '<a href="' . $comment->url_out . '" rel="external">' . $comment->name_out . '</a>';
		}

?>
			<li id="comment-<?php echo $comment->id; ?>" <?php echo $theme->k2_comment_class( $comment, $post ); ?>>
			<a href="#comment-<?php echo $comment->id; ?>" class="counter" title="<?php _e('Permanent Link to this Comment'); ?>"><?php echo $comment->id; ?></a>
			<span class="commentauthor"><?php echo $comment_url; ?></span>
			<small class="comment-meta"><a href="#comment-<?php echo $comment->id; ?>" title="<?php _e('Time of this Comment'); ?>"><?php $comment->date->out(); ?></a><?php if ( $comment->status == Comment::STATUS_UNAPPROVED ) : ?> <em><?php _e('In moderation'); ?></em><?php endif; ?></small>

			<div class="comment-content">
				<?php echo $comment->content_out; ?>

			</div>
			</li>

<?php
	}
}
else { ?>
			<li><?php _e('There are currently no comments.'); ?></li>
<?php } ?>
		</ol>

<?php 	if ( ! $post->info->comments_disabled ) { ?>
		<div class="comments">
			<h4 id="respond" class="reply"><?php _e('Leave a Reply'); ?></h4>
<?php	if ( Session::has_messages() ) {
		Session::messages_out();
	}

	$post->comment_form()->out();
} ?>
</div>
		<hr>

		</div>
<!-- /comments -->
