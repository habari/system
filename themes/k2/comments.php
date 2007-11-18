<!-- comments -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
    <hr>
    
    <div class="comments">
     <h4><span id="comments"><?php echo $post->comments->moderated->count; ?> Responses to <?php echo $post->title; ?></span></h4>
     <div class="metalinks">
      <span class="commentsrsslink"><a href="<?php echo $post->comment_feed_link; ?>">Feed for this Entry</a></span> <span class="trackbacklink">Trackback Address</span>
     </div>
     
     <ol id="commentlist">
<?php 
if ( $post->comments->moderated->count ) {
	foreach ( $post->comments->moderated as $comment ) {
?>

      <li id="comment-<?php echo $comment->id; ?>" class="comment">
       <a href="#comment-<?php echo $comment->id; ?>" class="counter" title="Permanent Link to this Comment"><?php echo $comment->id; ?></a>
       <span class="commentauthor"><a href="<?php echo $comment->url; ?>" rel="external"><?php echo $comment->name; ?></a></span>
       <small class="comment-meta"><a href="#comment-<?php echo $comment->id; ?>" title="Time of this comment"><?php echo $comment->date; ?></a><?php if ( $comment->status == Comment::STATUS_UNAPPROVED ) : ?> <em>In moderation</em><?php endif; ?></small>
       
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
