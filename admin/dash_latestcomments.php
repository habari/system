<div class="options">&nbsp;</div>

<div class="modulecore">
	<h2>Latest Comments</h2>

	<div class="handle">&nbsp;</div>

	<ul class="items">

		<?php
		foreach( DB::get_results( 'select distinct post_id from ( select date, post_id from {comments} where status = ? and type = ? order by date desc, post_id ) as post_ids limit 5;', array( Comment::STATUS_APPROVED, Comment::COMMENT ), 'Post' ) as $comment_post ):
		$post= DB::get_row( 'select * from {posts} where id = ?', array( $comment_post->post_id ) , 'Post' );

		?>
		<li class="item clear">
			<span class="titleanddate pct85"><a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a> <a href="#" class="date minor"><?php echo date('M j', strtotime($post->pubdate)); ?></a></span>
			<span class="comments pct15"><a href="<?php echo $post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $post->comments->approved->comments->count), $post->comments->approved->comments->count); ?>"><?php echo $post->comments->approved->comments->count; ?></a></span>
			<ul class="commentauthors pct85 minor">
				<?php
				$comment_count= 0;
				$comments= DB::get_results( 'SELECT * FROM {comments} WHERE post_id = ? AND status = ? AND type = ? ORDER BY date DESC LIMIT 5;', array( $comment_post->post_id, Comment::STATUS_APPROVED, Comment::COMMENT ), 'Comment' );
				foreach( $comments as $comment):
					$comment_count++;
					$opa = 'opa' . (100 - $comment_count * 15);
				?>
				<li><a href="<?php echo $comment->post->permalink; ?>#comment-<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), date('g:m a \o\n F jS, Y', strtotime($comment->date))); ?>" class="<?php echo $opa; ?>"><?php echo $comment->name; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php endforeach; ?>

	</ul>

</div>


<div class="optionswindow">
	<h2>Latest Comments</h2>

	<div class="handle">&nbsp;</div>

	<div class="optionscontent">
		<p>
			<label for="dummy" class="pct30"># of Entries</label>
			<select class="pct55">
				<option>10</option>
			</select>
		</p>

		<p class="buttons">
			<input type="submit" value="Submit">
		</p>
	</div>
</div>
