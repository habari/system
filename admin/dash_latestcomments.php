<div class="options">&nbsp;</div>

<div class="modulecore">
	<h2>Latest Comments</h2>

	<div class="handle">&nbsp;</div>

	<ul class="items">

		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 10, 'orderby' => 'date DESC' ) ) as $comment ): ?>
		<li class="item clear">
			<span class="titleanddate pct85"><a href="<?php echo $comment->post->permalink; ?>" class="title"><?php echo $comment->post->title; ?></a> <a href="#" class="date minor"><?php echo date('M j', strtotime($comment->post->pubdate)); ?></a></span>
			<span class="comments pct15"><a href="<?php echo $comment->post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $comment->post->comments->approved->comments->count), $comment->post->comments->approved->comments->count); ?>"><?php echo $comment->post->comments->approved->comments->count; ?></a></span>
			<ul class="commentauthors pct85 minor">
				<?php foreach($comment->post->comments->comments->approved as $comment): ?>
				<li><a href="<?php echo $comment->post->permalink; ?>#comment-<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), date('h.m on F jS, Y', strtotime($comment->post->pubdate))); ?>" class="opa100"><?php echo $comment->name; ?></a></li>
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
