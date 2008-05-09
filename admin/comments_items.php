<?php foreach( $comments as $comment ) : ?>

<div class="item clear" id="comment_<?php echo $comment->id; ?>">
	<div class="head clear">
		<span class="checkboxandtitle pct25">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1"></input>
			<?php if($comment->url != ''): ?>
			<a href="#" class="author"><?php echo $comment->name; ?></a>
			<?php else: ?>
			<?php echo $comment->name; ?>
			<?php endif; ?>
		</span>
		<span class="entry pct30"><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>"><?php echo $comment->post->title; ?></a></span>
    <span class="time pct10"><a href="#"><span class="dim">at</span> <?php echo date('H:i', strtotime($comment->date));?></a></span>
    <span class="date pct15"><a href="#"><span class="dim">on</span> <?php echo date('M d, Y', strtotime($comment->date));?></a></span>
		<ul class="dropbutton">
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'delete');">Delete</a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'spam');">Spam</a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'approve');">Approve</a></li>
			<li><a href="#" onclick="itemManage.update(<?php echo $comment->id; ?>, 'unapprove');">Unapprove</a></li>
			<li><a href="#">Edit</a></li>
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
