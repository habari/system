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
<!-- comments -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
    <hr>

    <div class="comments">
     <h4><span id="comments"><?php echo $post->comments->moderated->count; ?> <?php _e('Responses to'); ?> <?php echo $post->title; ?></span></h4>
     <div class="metalinks">
      <span class="commentsrsslink"><a href="<?php echo $post->comment_feed_link; ?>"><?php _e('Feed for this Entry'); ?></a></span>
     </div>

     <ol id="commentlist">
<?php
if ( $post->comments->moderated->count ) {
	foreach ( $post->comments->moderated as $comment ) {

		if ( $comment->url_out == '' ) {
			$comment_url = $comment->name_out;
		}
		else {
			$comment_url = '<a href="' . $comment->url_out . '" rel="external">' . $comment->name_out . '</a>';
		}

?>
      <li id="comment-<?php echo $comment->id; ?>" <?php echo $theme->k2_comment_class( $comment, $post ); ?>>
       <a href="#comment-<?php echo $comment->id; ?>" class="counter" title="<?php _e('Permanent Link to this Comment'); ?>"><?php echo $comment->id; ?></a>
       <span class="commentauthor"><?php echo $comment_url; ?></span>
       <small class="comment-meta"><a href="#comment-<?php echo $comment->id; ?>" title="<?php _e('Time of this Comment'); ?>"><?php $comment->date->out(); ?></a><?php if ( $comment->status == Comment::STATUS_UNAPPROVED ) : ?> <em><?php _e('In moderation'); ?></em><?php endif; ?></small>

       <div class="comment-content">
        <?php echo $comment->content_out; ?>

       </div>
      </li>

<?php
	}
}
else { ?>
      <li><?php _e('There are currently no comments.'); ?></li>
<?php } ?>
     </ol>

<?php if ( ! $post->info->comments_disabled ) { include_once( 'commentform.php' ); } ?>

     <hr>

    </div>
<!-- /comments -->
