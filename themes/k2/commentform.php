<!-- commentsform -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
     <div class="comments">
      <h4 id="respond" class="reply"><?php _e('Leave a Reply'); ?></h4>
<?php
if ( Session::has_messages() ) {
	Session::messages_out();
}
?>

      <form action="<?php URL::out( 'submit_feedback', array( 'id' => $post->id ) ); ?>" method="post" id="commentform">
       <div id="comment-personaldetails">
        <p>
         <input type="text" name="name" id="name" value="<?php echo $commenter_name; ?>" size="22" tabindex="1">
         <label for="name"><small><strong><?php _e('Name'); ?></strong></small><span class="required"><?php if (Options::get('comments_require_id') == 1) : ?> *<?php _e('Required'); ?><?php endif; ?></span></label>
        </p>
        <p>
         <input type="text" name="email" id="email" value="<?php echo $commenter_email; ?>" size="22" tabindex="2">
         <label for="email"><small><strong><?php _e('Mail'); ?></strong> (<?php _e('will not be published'); ?>)</small><span class="required"><?php if (Options::get('comments_require_id') == 1) : ?> *<?php _e('Required'); ?><?php endif; ?></span></label>
        </p>
        <p>
         <input type="text" name="url" id="url" value="<?php echo $commenter_url; ?>" size="22" tabindex="3">
         <label for="url"><small><strong><?php _e('Website'); ?></strong></small></label>
        </p>
       </div>
       <p>
<textarea name="content" id="content" cols="100" rows="10" tabindex="4">
<?php echo $commenter_content; ?>
</textarea>
       </p>
       <p>
        <input name="submit" type="submit" id="submit" tabindex="5" value="<?php _e('Submit'); ?>">
       </p>
       <div class="clear"></div>
      </form>
     </div>
<!-- /commentsform -->
