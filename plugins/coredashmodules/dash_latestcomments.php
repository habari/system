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

		<?php foreach ( $latestcomments_posts as $post ): ?>
		<li class="item clear">
			<span class="titleanddate pct85"><a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a> <a href="#" class="date minor"><?php $post->pubdate->out( 'M j' ); ?></a></span>
			<span class="comments pct15"><a href="<?php echo $post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $post->comments->approved->comments->count), $post->comments->approved->comments->count); ?>"><?php echo $post->comments->approved->comments->count; ?></a></span>
			<ul class="commentauthors pct85 minor">
				<?php
				$comment_count = 0;
				foreach( $latestcomments[$post->id] as $comment):
					$comment_count++;
					$opa = 'opa' . (100 - $comment_count * 15);
				?>
				<li><a href="<?php echo $comment->post->permalink; ?>#comment-<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), $comment->date->get( 'g:m a \o\n F jS, Y' ) ); ?>" class="<?php echo $opa; ?>"><?php echo $comment->name; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php endforeach; ?>

	</ul>
