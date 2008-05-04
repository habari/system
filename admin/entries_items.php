<?php	foreach($posts as $post) : ?>

<div class="item clear" id="post_<?php echo $post->id; ?>">
	<div class="head clear">
		<span class="checkboxandtitle pct30">
			<input type="checkbox" class="checkbox">
			<a href="#" class="title"><?php echo $post->title; ?></a>
		</span>
		<span class="state pct10"><a href="#"><?php echo $post->statusname; ?></a></span>
		<span class="author pct20"><a href="#"><span class="dim">by</span> <?php echo $post->author->username; ?></a></span>
		<span class="time pct10"><a href="#"><span class="dim">at</span> <?php echo date('H:i', strtotime($post->pubdate)); ?></a></span>
		<span class="date pct15"><a href="#"><span class="dim">on</span> <?php echo date('M j, Y', strtotime($post->pubdate)); ?></a></span>

		<ul class="dropbutton">
			<li><a href="#">Edit</a></li>
			<li><a href="#">Delete</a></li>
			<li><a href="#">Draft</a></li>
		</ul>
	</div>

	<span class="content" ><?php echo $post->pubdate; echo substr( strip_tags( $post->content ), 0, 200); ?>&hellip;</span>
</div>

<?php endforeach; ?>