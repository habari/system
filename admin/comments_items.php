<?php if(count($comments) != 0) :
	foreach( $comments as $comment ) : ?>

<div class="item clear <?php echo strtolower( $comment->statusname ); ?>" id="comment_<?php echo $comment->id; ?>">
	<div class="head clear">
		<span class="checkbox title pct25">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1">
			<?php if($comment->url != ''): ?>
			<a href="#" class="author edit-author" title="<?php echo $comment->name; ?>"><?php echo $comment->name; ?></a>
			<?php else: ?>
			<?php echo $comment->name; ?>
			<?php endif; ?>
		</span>
		<span class="title pct40"><span class="dim"><?php _e('in'); ?> '</span><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>" title="<?php printf( _t('Go to %s'), $comment->post->title ); ?>"><?php echo $comment->post->title; ?></a><span class="dim">'</span></span>
	    <span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="<?php URL::out('admin', array('page' => 'comments', 'status' => $comment->status, 'year' => $comment->date->year, 'month' => $comment->date->mon )); ?>" class="edit-date" title="<?php _e('Search for other comments from '. $comment->date->format('M, Y')) ?>"><?php $comment->date->out('M d, Y');?></a></span>
	    <span class="time pct10 dim"><?php _e('at'); ?> <span class="edit-time"><?php $comment->date->out('H:i');?></span></span>

		<ul class="dropbutton">
		<?php if ( $comment->status != Comment::STATUS_APPROVED ) : ?>
			<li><a href="#" onclick="itemManage.update( 'approve', <?php echo $comment->id; ?> );return false;" title="<?php _e('Approve this comment') ?>"><?php _e('Approve'); ?></a></li>
		<?php endif; ?>
		<?php if ( $comment->status != Comment::STATUS_UNAPPROVED ) : ?>
			<li><a href="#" onclick="itemManage.update( 'unapprove', <?php echo $comment->id; ?> );return false;" title="<?php _e('Unapprove this comment') ?>"><?php _e('Unapprove'); ?></a></li>
		<?php endif; ?>
		<?php if ( $comment->status != Comment::STATUS_SPAM ) :?>
			<li><a href="#" onclick="itemManage.update( 'spam', <?php echo $comment->id; ?> );return false;" title="<?php _e('Spam this comment') ?>"><?php _e('Spam'); ?></a></li>
		<?php endif; ?>
			<li><a href="#" onclick="itemManage.update( 'delete', <?php echo $comment->id; ?> );return false;" title="<?php _e('Delete this Comment') ?>"><?php _e('Delete'); ?></a></li>
			<li class="submit nodisplay"><a href="#" onclick="inEdit.update(); return false;" title="<?php _e('Submit changes') ?>"><?php _e('Update'); ?></a></li>
			<li class="cancel nodisplay" class="nodisplay"><a href="#" onclick="inEdit.deactivate(); return false;" title="<?php _e('Cancel changes') ?>"><?php _e('Cancel'); ?></a></li>
			<?php $theme->admin_comment_actions($comment); ?>
			<li><a href="<?php echo URL::get('admin', 'page=comment&id=' . $comment->id); ?>"><?php _e('Edit'); ?></a></li>
		</ul>
	</div>

	<div class="infoandcontent clear">
		<span class="authorinfo pct25 minor">
			<ul>
			<?php if ($comment->url != '')
				echo '<li><a class="edit-url" href="' . $comment->url . '">' . $comment->url . '</a></li>'."\r\n"; ?>
			<?php if ( $comment->email != '' )
				echo '<li><a class="edit-email" href="mailto:' . $comment->email . '">' . $comment->email . '</a></li>'."\r\n"; ?>
			</ul>
			<?php if ( $comment->status == Comment::STATUS_SPAM ) :?>
				<p><?php _e('Marked as spam'); ?></p>
			<?php endif; ?>

		</span>
		<span class="content edit-content area pct75"><?php echo $comment->content ?></span>
	</div>
</div>

<?php 	endforeach; 
else : ?>
<div class="message none">
	<p><?php _e('No comments could be found to match the query criteria.'); ?></p>
</div>
<?php endif; ?>
