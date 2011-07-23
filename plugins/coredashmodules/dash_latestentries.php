<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
	<ul class="items">

		<?php foreach((array)$recent_posts as $post): ?>
		<li class="item clear">
			<span class="date pct15 minor"><a href="<?php echo URL::get('display_entries_by_date', array('year' => $post->pubdate->get( 'Y' ), 'month' => $post->pubdate->get( 'm' ), 'day' => $post->pubdate->get( 'd' ) ) ); ?>" title="<?php printf(_t('Posted at %1$s'), $post->pubdate->get( 'g:m a \o\n F jS, Y' ) ); ?>"><?php $post->pubdate->out( 'M j' ); ?></a></span>
			<span class="title pct75"><a href="<?php URL::out('admin', 'page=publish&id=' . $post->id); ?>" title="<?php printf( _t('Edit \'%s\''), $post->title ); ?>"><?php echo $post->title; ?></a> <span class="dim"><?php _e('by'); ?></span> <a class="minor" href="<?php Site::out_url('admin'); ?>/user/<?php echo $post->author->username; ?>"><?php echo $post->author->displayname; ?></a></span>
			<span class="comments pct10"><a href="<?php echo $post->permalink; ?>#comments"><?php echo $post->comments->approved->count; ?></a></span>
		</li>
		<?php endforeach; ?>

	</ul>
