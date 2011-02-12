<ul id="recent_comments">
	<?php $comments = $content->recent_comments; foreach( $comments as $comment): ?>
		<li>
			<a href="<?php echo $comment->url ?>">
				<?php echo $comment->name; ?>
			</a> on
			<a href="<?php echo $comment->post->permalink; ?>">
				<?php echo $comment->post->title; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
