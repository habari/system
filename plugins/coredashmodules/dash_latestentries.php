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
	<ul class="items">

		<?php foreach($recent_posts as $post): ?>
		<li class="item clear">
			<span class="date pct15 minor"><a href="<?php echo URL::get('display_entries_by_date', array('year' => $post->pubdate->get( 'Y' ), 'month' => $post->pubdate->get( 'm' ), 'day' => $post->pubdate->get( 'd' ) ) ); ?>" title="<?php printf(_t('Posted at %1$s'), $post->pubdate->get( 'g:m a \o\n F jS, Y' ) ); ?>"><?php $post->pubdate->out( 'M j' ); ?></a></span>
			<span class="title pct75"><a href="<?php echo $post->permalink; ?>"><?php echo $post->title; ?></a> <a class="minor" href="<?php Site::out_url('habari'); ?>/admin/user/<?php echo $post->author->username; ?>"> <?php _e('by'); ?> <?php echo $post->author->displayname; ?></a></span>
			<span class="comments pct10"><a href="<?php echo $post->permalink; ?>#comments"><?php echo $post->comments->approved->count; ?></a></span>
		</li>
		<?php endforeach; ?>

	</ul>
