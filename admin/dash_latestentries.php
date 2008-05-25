<div class="options">&nbsp;</div>

<div class="modulecore">
	<h2><a href="<?php Site::out_url('admin'); ?>/entries?type=1"><?php _e('Latest Entries'); ?></a></h2>

	<div class="handle">&nbsp;</div>

	<ul class="items">

		<?php foreach($recent_posts as $post): ?>
		<li class="item clear">
			<span class="date pct15 minor"><a href="<?php echo URL::get('display_entries_by_date', array('year' => date('Y', strtotime($post->pubdate)), 'month' => date('m', strtotime($post->pubdate)), 'day' => date('d', strtotime($post->pubdate)))); ?>" title="<?php printf(_t('Posted at %1$s'), date('g:m a \o\n F jS, Y', strtotime($post->pubdate))); ?>"><?php echo date('M j', strtotime($post->pubdate)); ?></a></span>
			<span class="title pct75"><a href="<?php echo $post->permalink; ?>"><?php echo $post->title; ?></a> <a class="minor" href="<?php Site::out_url('habari'); ?>/admin/user/<?php echo $post->author->username; ?>"> <?php _e('by'); ?> <?php echo $post->author->displayname; ?></a></span>
			<span class="comments pct10"><a href="<?php echo $post->permalink; ?>#comments"><?php echo $post->comments->approved->count; ?></a></span>
		</li>
		<?php endforeach; ?>

	</ul>
</div>

<div class="optionswindow">
	<h2><?php _e('Latest Entries'); ?></h2>

	<div class="handle">&nbsp;</div>

	<div class="optionscontent">
		<p>
			<label for="dummy" class="pct30"><?php _e('# of Entries'); ?></label>
			<select class="pct55">
				<option>10</option>
			</select>
		</p>

		<p class="buttons">
			<input type="submit" value="<?php _e('Submit'); ?>">
		</p>
	</div>
</div>
