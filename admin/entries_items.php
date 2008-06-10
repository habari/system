<?php	foreach($posts as $post) : ?>

<div class="item clear <?php echo $post->statusname; ?>" id="post_<?php echo $post->id; ?>">
	<div class="head clear">
		<span class="checkbox title pct35">
			<input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $post->id; ?>]" id="checkbox_ids[<?php echo $post->id; ?>]">
			<a href="<?php echo $post->permalink; ?>" class="title" title="<?php _e('Edit \''.$post->title.'\'') ?>"><?php echo $post->title; ?></a>
		</span>
		<span class="state pct10"><a href="<?php URL::out('admin', array('page' => 'entries', 'type' => $post->content_type, 'status' => $post->status ) ); ?>" title="<?php _e('Search for other '.$post->statusname.' items'); ?>"><?php echo $post->statusname; ?></a></span>
		<span class="author pct20"><span class="dim"><?php _e('by'); ?></span> <a href="<?php URL::out('admin', array('page' => 'entries', 'user_id' => $post->user_id, 'type' => $post->content_type, 'status' => 'any') ); ?>" title="<?php _e('Search for other items by '.$post->author->displayname) ?>"><?php echo $post->author->displayname; ?></a></span>
		<span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="<?php URL::out('admin', array('page' => 'entries', 'type' => $post->content_type, 'year_month' => date( 'Y-m', strtotime( $post->pubdate ) ) ) ); ?>" title="<?php _e('Search for other items from '.date('M, Y', strtotime($post->pubdate))) ?>"><?php echo date('M j, Y', strtotime($post->pubdate)); ?></a></span>
		<span class="time pct10"><span class="dim"><?php _e('at'); ?> <?php echo date('H:i', strtotime($post->pubdate)); ?></span></span>

		<ul class="dropbutton">
			<li><a href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>" title="<?php _e('Edit \''.$post->title.'\'') ?>"><?php _e('Edit'); ?></a></li>
			<li><a href="#" onclick="itemManage.remove(<?php echo $post->id; ?>, 'post');return false;" title="<?php _e('Delete this item') ?>"><?php _e('Delete'); ?></a></li>
		</ul>
	</div>

	<span class="content" ><?php echo substr( strip_tags( $post->content ), 0, 250); ?>&hellip;</span>
</div>

<?php endforeach; ?>
