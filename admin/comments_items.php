<?php foreach( $comments as $comment ) : ?>

<div class="item clear" id="comment_<?php echo $comment->id; ?>">
	<div class="head clear">
		<span class="checkboxandtitle pct25">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1">
			<?php if($comment->url != ''): ?>
			<a href="#" class="author"><?php echo $comment->name; ?></a>
			<?php else: ?>
			<?php echo $comment->name; ?>
			<?php endif; ?>
		</span>
		<span class="entry pct30"><span class="dim"><?php _e('in'); ?> '</span><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>"><?php echo $comment->post->title; ?></a><span class="dim">'</span></span>
    <span class="time pct10"><?php _e('at'); ?> <a href="#"><span class="dim"></span><?php echo date('H:i', strtotime($comment->date));?></a></span>
    <span class="date pct15"><?php _e('on'); ?> <a href="#"><span class="dim"></span><?php echo date('M d, Y', strtotime($comment->date));?></a></span>
		<ul class="dropbutton">
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'approve');return false;"><?php _e('Approve'); ?></a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'unapprove');return false;"><?php _e('Unapprove'); ?></a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'spam');return false;"><?php _e('Spam'); ?></a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'delete');return false;"><?php _e('Delete'); ?></a></li>
			<li><a href="#"><?php _e('Edit'); ?></a></li>
		</ul>
	</div>

	<div class="infoandcontent clear">
		<span class="authorinfo pct25 minor">
			<?php if ($comment->url != '')
				echo '<a href="' . $comment->url . '">' . $comment->url . '</a>'."\r\n"; ?>
			<?php if ( $comment->email != '' )
				echo '<a href="mailto:' . $comment->email . '">' . $comment->email . '</a>'."\r\n"; ?>
		</span>
		<span class="content pct75"><?php echo $comment->content ?></span>
	</div>
</div>

<?php endforeach; ?>
