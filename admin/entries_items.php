<?php	foreach($posts as $post) : ?>

<div class="item clear" id="post_<?php echo $post->id; ?>">
	<div class="head clear">
		<span class="checkboxandtitle pct30">
			<input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $post->id; ?>]" id="checkbox_ids[<?php echo $post->id; ?>]"></input>
			<a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a>
		</span>
		<span class="state pct10"><a href="<?php URL::out('admin', array('page' => 'entries', 'type' => $post->content_type, 'status' => $post->status ) ); ?>"><?php echo $post->statusname; ?></a></span>
		<span class="author pct20"><a href="<?php URL::out('admin', array('page' => 'entries', 'user_id' => $post->user_id, 'type' => $post->content_type, 'status' => 'any') ); ?>"><span class="dim">by</span> <?php echo $post->author->displayname ? $post->author->displayname : $post->author->username; ?></a></span>
		<span class="date pct15"><a href="<?php URL::out('admin', array('page' => 'entries', 'type' => $post->content_type, 'year_month' => date( 'Y-m', strtotime( $post->pubdate ) ) ) ); ?>"><span class="dim">on</span> <?php echo date('M j, Y', strtotime($post->pubdate)); ?></a></span>
		<span class="time pct10"><span class="dim">at <?php echo date('H:i', strtotime($post->pubdate)); ?></span></span>

		<ul class="dropbutton">
			<li><a href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>">Edit</a></li>
			<li><a href="#" onclick="itemManage.remove(<?php echo $post->id; ?>, 'post');return false;">Delete</a></li>
		</ul>
	</div>

	<span class="content" ><?php echo substr( strip_tags( $post->content ), 0, 200); ?>&hellip;</span>
</div>

<?php endforeach; ?>
