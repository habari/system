<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php if ( count($posts) != 0 ) :
	foreach ( $posts as $post ) :
		$post_permissions = $post->get_access();
?>
<div class="item clear <?php echo $post->statusname; ?>" id="post_<?php echo $post->id; ?>">
	<div class="head clear">
		<?php if ( ACL::access_check( $post_permissions, 'delete' ) ) { ?>
		<span class="checkbox title pct5">
			<input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $post->id; ?>]" id="checkbox_ids[<?php echo $post->id; ?>]">
		</span>
		<?php } ?>
		<span class="checkbox title pct30">
			<?php if ( ACL::access_check( $post_permissions, 'edit' ) ) { ?>
				<a href="<?php echo URL::out('admin', 'page=publish&id=' . $post->id); ?>" class="title" title="<?php _e('Edit \'%s\'', array( Utils::htmlspecialchars( $post->title ) ) ) ?>"><?php echo ($post->title == '') ? '&nbsp;' : Utils::htmlspecialchars( $post->title ); ?></a>
			<?php } else { ?>
				<?php echo ($post->title == '') ? '&nbsp;' : Utils::htmlspecialchars( $post->title ); ?>
			<?php } ?>
		</span>
		<span class="state pct10"><a href="<?php URL::out('admin', array('page' => 'posts', 'type' => $post->content_type, 'status' => $post->status ) ); ?>" title="<?php _e('Search for other %s items', array( MultiByte::ucfirst( Plugins::filter( "post_status_display", $post->statusname ) ) ) ); ?>"><?php echo MultiByte::ucfirst( Plugins::filter( "post_status_display", $post->statusname ) ); ?></a></span>
		<span class="author pct20"><span class="dim"><?php _e('by'); ?></span> <a href="<?php URL::out('admin', array('page' => 'posts', 'user_id' => $post->user_id, 'type' => $post->content_type, 'status' => 'any') ); ?>" title="<?php _e('Search for other items by %s', array( $post->author->displayname ) ) ?>"><?php echo $post->author->displayname; ?></a></span>
		<span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="<?php URL::out('admin', array('page' => 'posts', 'type' => $post->content_type, 'year_month' => $post->pubdate->get('Y-m') ) ); ?>" title="<?php _e('Search for other items from %s', array( $post->pubdate->get( 'M, Y' ) ) ); ?>"><?php $post->pubdate->out( HabariDateTime::get_default_date_format() ); ?></a></span>
		<span class="time pct10"><span class="dim"><?php _e('at'); ?> <?php $post->pubdate->out( HabariDateTime::get_default_time_format()); ?></span></span>

		<ul class="dropbutton">
			<?php $actions = array(
				'edit' => array( 'url' => URL::get( 'admin', 'page=publish&id=' . $post->id ), 'title' => _t( 'Edit \'%s\'', array( $post->title ) ), 'label' => _t( 'Edit' ), 'permission' => 'edit' ),
				'view' => array( 'url' => $post->permalink . '?preview=1', 'title' => _t( 'View \'%s\'', array( $post->title ) ), 'label' => _t( 'View' ) ),
				'remove' => array( 'url' => 'javascript:itemManage.remove('. $post->id . ', \'post\');', 'title' => _t( 'Delete this item' ), 'label' => _t( 'Delete' ), 'permission' => 'delete' )
			);
			$actions = Plugins::filter('post_actions', $actions, $post);
			foreach( $actions as $action ) :
			?>
				<?php if ( !isset( $action['permission'] ) || ACL::access_check( $post_permissions, $action['permission'] ) ) { ?>
				<li><a href="<?php echo $action['url']; ?>" title="<?php echo $action['title']; ?>"><?php echo $action['label']; ?></a></li>
				<?php } ?>
			<?php endforeach; ?>
		</ul>
	</div>

	<span class="content" ><?php echo MultiByte::substr( strip_tags( $post->content ), 0, 250); ?>&hellip;</span>
</div>

<?php endforeach;
else : ?>
<div class="message none">
	<p><?php _e('No posts could be found to match the query criteria.'); ?></p>
</div>
<?php endif; ?>
