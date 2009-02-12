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
<?php include 'header.php'; ?>

			<div id="main-posts">
				<div class="<?php echo $page_class?>">
				<?php if ( is_array( $post->tags ) && !empty($post->tags) ) : ?>
					<div class="post-tags">
						<?php echo $post->tags_out;?>
					</div>
				<?php endif ?>
					<div class="post-title">
						<h3>
							<a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a>
						</h3>
					</div>
					<div class="post-entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="post-footer">
					<?php if ( $loggedin ) : ?>
						<span class="post-edit">
						<a href="<?php echo $post->editlink; ?>" title="<?php _e( "Edit post" ); ?>"><?php _e( "Edit" ); ?></a>
						</span>
					<?php endif;?>
					</div>
				</div>
			</div>
		</div>
		<div id="top-secondary">
			<?php include'sidebar.php' ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
<div id="page-bottom">
	<div id="wrapper-bottom">
		<div id="bottom-primary">
		
		<?php include 'comments.php'; ?>
		
		<!-- comment form -->
		
		<?php include 'commentform.php'; ?>
		
		<!-- /comment form -->

<?php include 'footer.php'; ?>
