<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<div class="group item clear" id="item-<?php echo $group->id; ?>">
	<div class="head">
		<h4><a href="<?php echo URL::get('admin', 'page=group&id=' . $group->id) ?>" title="<?php _e('Edit group'); ?>"><?php echo $group->name; ?></a></h4>
		<ul class="dropbutton">
			<?php $actions = array(
				'edit' => array('url' => URL::get('admin', 'page=group&id=' . $group->id), 'title' => _t('Edit group'), 'label' => _t('Edit')),
				'remove' => array('url' => 'javascript:itemManage.remove('. $group->id . ', \'group\');', 'title' => _t('Delete this group'), 'label' => _t('Delete'))
			);
			$actions = Plugins::filter('group_actions', $actions);
			foreach($actions as $action):
			?>
				<li><a href="<?php echo $action['url']; ?>" title="<?php echo $action['title']; ?>"><?php echo $action['label']; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php if(count($users) > 0): ?>
		<p class="users"><?php echo _t('Members: ') . Format::and_list($users); ?></p>
	<?php else: ?>
		<p class="users"><?php echo _t('No members'); ?></p>
	<?php endif; ?>
</div>
