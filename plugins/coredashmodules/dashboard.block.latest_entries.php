<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
?>
	<ul class="items">
		<?php foreach((array)$content->recent_posts as $post): ?>
		<li class="item">
			<?php /* @locale Time formats according to http://php.net/manual/en/function.date.php */ $ptime = $post->pubdate->get( _t( 'g:i' ) ); ?>
			<?php /* @locale Date formats according to http://php.net/manual/en/function.date.php */ $pdate = $post->pubdate->get( _t( 'F jS, Y' ) ); ?>
			<span class="date"><a href="<?php echo URL::get('display_entries_by_date', array('year' => $post->pubdate->get( 'Y' ), 'month' => $post->pubdate->get( 'm' ), 'day' => $post->pubdate->get( 'd' ) ) ); ?>" title="<?php printf(_t('Posted at %1$s on %2$s'), $ptime, $pdate ); ?>"><?php /* @locale Date formats according to http://php.net/manual/en/function.date.php */ $post->pubdate->out(  _t( 'M j' ) ); ?></a></span>
			<span class="title"><a href="<?php URL::out('admin', 'page=publish&id=' . $post->id); ?>" title="<?php printf( _t('Edit \'%s\''), $post->title ); ?>"><?php echo $post->title; ?></a> <span><?php _e('by'); ?></span> <a href="<?php Site::out_url('admin'); ?>/user/<?php echo $post->author->username; ?>"><?php echo $post->author->displayname; ?></a></span>
			<span class="comments"><a href="<?php echo $post->permalink; ?>#comments"><?php echo $post->comments->approved->count; ?></a></span>
		</li>
		<?php endforeach; ?>

	</ul>
