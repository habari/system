				<div class="container">
					<p class="column span-5"><?php _e('Content State'); ?></p>
					<p class="column span-14 last">
					 	<label><?php echo Utils::html_select( 'status', array_flip($statuses), $post->status == Post::status( 'scheduled' ) ? Post::status( 'published' ) : $post->status, array( 'class'=>'longselect') ); ?></label>
					</p>
				</div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Comments Allowed'); ?></p> 	<p class="column span-14 last"><input type="checkbox" name="comments_enabled" class="styledformelement" value="1" <?php echo ( $post->info->comments_disabled == 1 ) ? '' : 'checked'; ?>></p></div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Publication Time'); ?></p>	<p class="column span-14 last"><input type="text" name="pubdate" id="pubdate" class="styledformelement" value="<?php echo $post->pubdate; ?>"> <em>To schedule a post, enter a future date</em></p></div>
				<hr>
				<div class="container"><p class="column span-5"><?php _e('Content Address'); ?></p>		<p class="column span-14 last"><input type="text" name="newslug" id="newslug" class="styledformelement" value="<?php echo $post->slug; ?>"></p></div>

