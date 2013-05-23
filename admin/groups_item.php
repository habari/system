<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<div class="item group" id="item-<?php echo $group->id; ?>">
	<div>
		<h4><a href="<?php echo URL::get('display_group', 'id=' . $group->id) ?>" title="<?php _e('Edit group'); ?>"><?php echo $group->name; ?></a></h4>
		<?php
		$group_actions = FormControlDropbutton::create('group_actions');
		$group_actions->append(
			FormControlSubmit::create('edit')->set_caption(_t('Edit'))
				->set_url(URL::get( 'display_group', 'id=' . $group->id ))
				->set_property('title', _t( 'Edit \'%s\'', array( $group->title ) ) )
				->set_enable(function($control) {
					return User::identify()->can('manage_groups');
				})
		);
		$group_actions->append(
			FormControlSubmit::create('delete')->set_caption(_t('Delete'))
				->set_url('javascript:itemManage.remove('. $group->id . ', \'group\');')
				->set_property('title', _t( 'Delete \'%s\'', array( $group->name ) ) )
				->set_enable(function($control) {
					return User::identify()->can('manage_groups');
				})
		);
		echo $group_actions->pre_out();
		echo $group_actions->get($theme);
		// @todo Add back in filtering of group actions
		// $actions = Plugins::filter('group_actions', $actions);
		?>
	</div>
	<?php if ( count($users) > 0 ): ?>
		<p class="users"><?php echo _t('Members: ') . Format::and_list($users); ?></p>
	<?php else: ?>
		<p class="users"><?php _e( 'No members' ); ?></p>
	<?php endif; ?>
</div>
