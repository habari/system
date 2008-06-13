<?php foreach( $comments as $comment ) : ?>

<div class="item clear <?php echo strtolower( $comment->statusname ); ?>" id="comment_<?php echo $comment->id; ?>">
	<div class="head clear">
		<span class="checkbox title pct25">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1">
			<?php if($comment->url != ''): ?>
			<a href="#" class="author"><?php echo $comment->name; ?></a>
			<?php else: ?>
			<?php echo $comment->name; ?>
			<?php endif; ?>
		</span>
		<span class="title pct40"><span class="dim"><?php _e('in'); ?> '</span><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>" title="<?php _e('Go to \''.$comment->post->title.'\'') ?>"><?php echo $comment->post->title; ?></a><span class="dim">'</span></span>
	    <span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="#" title="<?php _e('Search for other comments from '.date('M, Y', strtotime($comment->date))) ?>"><?php echo date('M d, Y', strtotime($comment->date));?></a></span>
	    <span class="time pct10 dim"><?php _e('at'); ?> <?php echo date('H:i', strtotime($comment->date));?></span>

		<ul class="dropbutton">
		<?php if ( $comment->status != Comment::STATUS_APPROVED ) : ?>
			<li><a href="#" onclick="itemManage.update( 'approve', <?php echo $comment->id; ?> );return false;" title="<?php _e('Approve this comment') ?>"><?php _e('Approve'); ?></a></li>
		<?php endif; ?>
		<?php if ( $comment->status != Comment::STATUS_UNAPPROVED ) : ?>
			<li><a href="#" onclick="itemManage.update( 'unapprove', <?php echo $comment->id; ?> );return false;" title="<?php _e('Unapprove this comment') ?>"><?php _e('Unapprove'); ?></a></li>
		<?php endif; ?>
		<?php if ( $comment->status != Comment::STATUS_SPAM ) :?>
			<li><a href="#" onclick="itemManage.update( 'spam', <?php echo $comment->id; ?> );return false;" title="<?php _e('Spam this comment') ?>"><?php _e('Spam'); ?></a></li>
		<?php endif; ?>
			<li><a href="#" onclick="itemManage.update( 'delete', <?php echo $comment->id; ?> );return false;" title="<?php _e('Delete this Comment') ?>"><?php _e('Delete'); ?></a></li>
			<li><a href="#"><?php _e('Edit'); ?></a></li>
		</ul>
	</div>

	<div class="infoandcontent clear">
		<span class="authorinfo pct25 minor">
			<?php if ($comment->url != '')
				echo '<a href="' . $comment->url . '">' . $comment->url . '</a>'."\r\n"; ?>
			<?php if ( $comment->email != '' )
				echo '<a href="mailto:' . $comment->email . '">' . $comment->email . '</a>'."\r\n"; ?>

			<?php if ( $comment->status == Comment::STATUS_SPAM ) :?>
				<p>Marked as spam</p>
			<?php endif; ?>

		</span>
		<span class="content pct75"><?php echo $comment->content ?></span>
	</div>
</div>

<?php endforeach; ?>
