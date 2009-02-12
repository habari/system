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
<?php $theme->display ( 'header' ); ?>
<!--begin content-->
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent" class="span-15 append-2">
			<!--begin single post navigation-->
			<div id="post-nav">
				<?php if ( $previous = $post->ascend() ): ?>
				<span class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></span>
				<?php endif; ?>
				<?php if ( $next = $post->descend() ): ?>
				<span class="right"><a href="<?php echo $next->permalink ?>" title="<?php echo $next->slug ?>"><?php echo $next->title ?></a> &raquo;</span>
				<?php endif; ?>
			</div>
			<!--begin loop-->
				<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
						<h2 class="prepend-2"><a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h2>
						<div class="cal"><?php echo $post->pubdate_out; ?></div>
						<div class="entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="entryMeta">
						<?php if ( is_array( $post->tags ) ) { ?>
						<div class="tags"><?php _e('Tagged:'); ?> <?php echo $post->tags_out; ?></div>
						<?php } ?>
					</div><br>
						<?php if ( $loggedin ) { ?>
						<a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit post'); ?>"><?php _e('Edit'); ?></a>
						<?php } ?>

				</div>

			<!--end loop-->
			<?php include 'commentform.php'; ?>
			</div>
		<!--end primary content-->
		<?php $theme->display ( 'sidebar' ); ?>
	</div>
	<!--end content-->
	<?php $theme->display ( 'footer' ); ?>
