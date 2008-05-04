<div class="container">
	<p class="pct25"><?php _e('Content State'); ?></p>
	<p class="pct75">
	 	<label><?php echo Utils::html_select( 'status', array_flip($statuses), $post->status, array( 'class'=>'longselect') ); ?></label>
	</p>
</div>
<hr>
<div class="container"><p class="pct25"><?php _e('Comments Allowed'); ?></p> 	<p class="pct75"><input type="checkbox" name="comments_enabled" class="styledformelement" value="1" <?php echo ( $post->info->comments_disabled == 1 ) ? '' : 'checked'; ?>></p></div>
<hr>
<div class="container"><p class="pct25"><?php _e('Publication Time'); ?></p>	<p class="pct75"><input type="text" name="pubdate" id="pubdate" class="styledformelement" value="<?php echo $post->pubdate; ?>"></p></div>
<hr>
<div class="container"><p class="pct25"><?php _e('Content Address'); ?></p>		<p class="pct75"><input type="text" name="newslug" id="newslug" class="styledformelement" value="<?php echo $post->slug; ?>"></p></div>
