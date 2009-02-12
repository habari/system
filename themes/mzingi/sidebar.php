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
	<!--begin secondary content-->
	<div id="secondaryContent" class="span-7 last">
		<?php $theme->display ( 'searchform' ); ?>
	<h3><a id="rss" href="<?php $theme->feed_alternate(); ?>" class="block"><?php _e('Subscribe to Feed'); ?></a></h3>
	<h2 id="site"><?php _e('Navigation'); ?></h2>
	<ul id="nav">
		<li><a href="<?php Site::out_url( 'habari' ); ?>"><?php _e('Home'); ?></a></li>
		<?php
		// List Pages
		foreach ( $pages as $page ) {
			echo '<li><a href="' . $page->permalink . '" title="' . $page->title . '">' . $page->title . '</a></li>' . "\n";
		}
		?>
	</ul>

	<h2 id="aside"><?php _e('Asides'); ?></h2>
	<ul id="asides">
		<?php
	          foreach($asides as $post):
              echo '<li><span class="date">';
              echo $post->pubdate->out('F j, Y') . ' - ' . '</span>';
              echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>'. $post->content_out;
              echo '</li>';

 		?>
	<?php endforeach; ?>
   </ul>

		<h2><?php _e('More Posts'); ?></h2>
		<ul id="moreposts">
			<?php foreach($more_posts as $post): ?>
				<?php
				echo '<li>';
				echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>';
				echo '</li>';
				?>
			<?php endforeach; ?>
		</ul>

	<h2 id="commentheading"><?php _e('Recent Comments'); ?></h2>
	<ul id="recentcomments">

	<?php foreach($recent_comments as $comment): ?>
	<li><a href="<?php echo $comment->url ?>"><?php echo $comment->name ?></a> <?php _e('on'); ?> <a href="<?php echo $comment->post->permalink; ?>"><?php echo $comment->post->title; ?></a></li>
	<?php endforeach; ?>
	</ul>
	<h2 id="loginheading"><?php _e('User Login'); ?></h2>
	<?php $theme->display ( 'loginform' ); ?>

	</div>
	<!--end secondary content-->
