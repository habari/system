<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
<?php if ( !$post->info->comments_disabled ) : ?>
<div id="comment-form">
<?php if ( Session::has_messages() ) Session::messages_out(); ?>
	<form action="<?php URL::out( 'submit_feedback', array( 'id' => $post->id ) ); ?>" method="post" id="commentform">
		<div id="comment-personaldetails">
			<p>
				<input type="text" name="name" id="name" value="<?php echo $commenter_name; ?>" size="30" tabindex="1">
				<label for="name"><strong><?php _e( "Name" ); ?></strong><?php if (Options::get('comments_require_id') == 1) : ?> <?php _e( "(Required)" ); ?><?php endif; ?></label>
			</p>
			<p>
				<input type="text" name="email" id="email" value="<?php echo $commenter_email; ?>" size="30" tabindex="2">
				<label for="email"><strong><?php _e( "Mail" ); ?></strong><?php if (Options::get('comments_require_id') == 1) : ?> <?php _e( "(will not be published - Required)" ); ?><?php endif; ?></label>
			</p>
			<p>
				<input type="text" name="url" id="url" value="<?php echo $commenter_url; ?>" size="30" tabindex="3">
				<label for="url"><strong><?php _e( "Website" ); ?></strong></label>
			</p>
		</div>
		<p>
<textarea name="content" id="content" cols="60" rows="10" tabindex="4">
<?php if ( isset( $details['content'] ) ) { echo $details['content']; } ?>
</textarea>
		</p>
		<p>
			<input name="submit" type="submit" id="submit" tabindex="5" value="<?php _e( "Submit" ); ?>">
		</p>
		<div class="clear"></div>
	</form>
</div>
<?php else: ?> 
<div id="comments-closed">
	<p><?php _e( "Comments are closed for this post" ); ?></p>
</div>
<?php endif; ?>
