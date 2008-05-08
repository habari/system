<div class="options">&nbsp;</div>

<div class="modulecore">
	<h2>Latest Comments</h2>

	<div class="handle">&nbsp;</div>

	<ul class="items">

		<?php
		$mindate= DB::get_value('select min(`date`) from (select `date` FROM {comments} where status = ? and `type` = ? group by post_id order by `date` desc limit 5) as date_foo;', array(Comment::STATUS_APPROVED, Comment::COMMENT));
		foreach( DB::get_results('select p.* from {posts} p, {comments} c where p.id = c.post_id AND c.status = ? and c.`type` = ? group by p.id order by `date` desc limit 5;', array(Comment::STATUS_APPROVED, Comment::COMMENT), 'Post') as $post ):
		?>
		<li class="item clear">
			<span class="titleanddate pct85"><a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a> <a href="#" class="date minor"><?php echo date('M j', strtotime($post->pubdate)); ?></a></span>
			<span class="comments pct15"><a href="<?php echo $post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $post->comments->approved->comments->count), $post->comments->approved->comments->count); ?>"><?php echo $post->comments->approved->comments->count; ?></a></span>
			<ul class="commentauthors pct85 minor">
				<?php
				$comment_count= 0;
				$comments = DB::get_results('SELECT * FROM {comments} WHERE post_id = ? AND status = ? AND `date` >= ? AND `type` = ?', array($post->id, Comment::STATUS_APPROVED, $mindate, Comment::COMMENT), 'Comment');
				foreach($comments as $comment):
					$comment_count++;
					if($comment_count > 5) {
					 	break;
					}
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
