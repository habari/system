<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="group item clear" id="item-<?php echo $group->id; ?>">
	<div class="head">
		<h4><a href="<?php echo URL::get('admin', 'page=group&id=' . $group->id) ?>" title="<?php _e('Edit group'); ?>"><?php echo $group->name; ?></a></h4>
		<ul class="dropbutton">
			<?php $actions = array(
				'edit' => array('url' => URL::get('admin', 'page=group&id=' . $group->id), 'title' => _t('Edit group'), 'label' => _t('Edit')),
				'remove' => array('url' => 'javascript:itemManage.remove('. $group->id . ', \'group\');', 'title' => _t('Delete this group'), 'label' => _t('Delete'))
			);
			$actions = Plugins::filter('group_actions', $actions);
			foreach ( $actions as $action ):
			?>
				<li><a href="<?php echo $action['url']; ?>" title="<?php echo $action['title']; ?>"><?php echo $action['label']; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php if ( count($users) > 0 ): ?>
		<p class="users"><?php echo _t('Members: ') . Format::and_list($users); ?></p>
	<?php else: ?>
		<p class="users"><?php _e( 'No members' ); ?></p>
	<?php endif; ?>
</div>
