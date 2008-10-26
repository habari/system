<?php if(count($comments) != 0) :
	foreach( $comments as $comment ) : ?>

<div class="item clear <?php echo strtolower( $comment->statusname ); ?>" id="comment_<?php echo $comment->id; ?>" style="<?php echo Plugins::filter('comment_style', '', $comment); ?>">
	<div class="head clear">
		<span class="checkbox title pct25">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1">
			<?php if($comment->url != ''): ?>
			<a href="#" class="author edit-author" title="<?php echo htmlspecialchars( $comment->name ); ?>"><?php echo htmlspecialchars( $comment->name ); ?></a>
			<?php else: ?>
			<?php echo htmlspecialchars( $comment->name ); ?>
			<?php endif; ?>
		</span>
		<span class="title pct40"><span class="dim"><?php _e('in'); ?> '</span><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>" title="<?php printf( _t('Go to %s'), $comment->post->title ); ?>"><?php echo $comment->post->title; ?></a><span class="dim">'</span></span>
	    <span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="<?php URL::out('admin', array('page' => 'comments', 'status' => $comment->status, 'year' => $comment->date->year, 'month' => $comment->date->mon )); ?>" class="edit-date" title="<?php _e('Search for other comments from '. $comment->date->format('M, Y')) ?>"><?php $comment->date->out('M d, Y');?></a></span>
	    <span class="time pct10 dim"><?php _e('at'); ?> <span class="edit-time"><?php $comment->date->out('H:i');?></span></span>

		<ul class="dropbutton">
			<?php
			$actions = array();
			if ( $comment->status != Comment::STATUS_APPROVED ) {
				$actions['approve']= array('url' => 'javascript:itemManage.update(\'approve\',' . $comment->id . ');', 'title' => _t('Approve this comment'), 'label' => _t('Approve'));
			}
			if ( $comment->status != Comment::STATUS_UNAPPROVED ) {
				$actions['unapprove']= array('url' => 'javascript:itemManage.update(\'unapprove\',' . $comment->id . ');', 'title' => _t('Unapprove this comment'), 'label' => _t('Unapprove'));
			}
			if ( $comment->status != Comment::STATUS_SPAM ) {
				$actions['spam']= array('url' => 'javascript:itemManage.update(\'spam\',' . $comment->id . ');', 'title' => _t('Spam this comment'), 'label' => _t('Spam'));
			}
			$actions['delete']= array('url' => 'javascript:itemManage.update(\'delete\',' . $comment->id . ');', 'title' => _t('Delete this comment'), 'label' => _t('Delete'));

			$actions['edit']= array('url' => URL::get('admin', 'page=comment&id=' . $comment->id), 'title' => _t('Edit this comment'), 'label' => _t('Edit'));

			$actions['submit']= array('url' => 'javascript:inEdit.update();', 'title' => _t('Submit changes'), 'label' => _t('Update'), 'nodisplay' => TRUE);
			$actions['cancel']= array('url' => 'javascript:inEdit.deactivate();', 'title' => _t('Cancel changes'), 'label' => _t('Cancel'), 'nodisplay' => TRUE);

			$actions = Plugins::filter('comment_actions', $actions, $comment);
			foreach($actions as $act_id => $action):
			?>
			<li class="<?php echo $act_id; if(isset($action['nodisplay']) && $action['nodisplay'] == true) { echo ' nodisplay'; } ?>"><a href="<?php echo $action['url']; ?>" title="<?php echo $action['title']; ?>"><?php echo $action['label']; ?></a></li>
			<?php endforeach; ?>
			<?php $theme->admin_comment_actions($comment); ?>
		</ul>
	</div>

	<div class="infoandcontent clear">
		<span class="authorinfo pct25 minor">
			<ul>
				<?php if ($comment->url != '') {
					echo '<li><a class="edit-url" href="' . $comment->url . '">' . $comment->url . '</a></li>'."\r\n";
					} else {
						echo '<li class="empty">no url given</li>';
					} ?>
				<?php if ( $comment->email != '' ) {
					echo '<li><a class="edit-email" href="mailto:' . $comment->email . '">' . $comment->email . '</a></li>'."\r\n";
					} else {
						echo '<li class="empty">no email provided</li>';
					} ?>
			</ul>
			<?php if ( $comment->status == Comment::STATUS_SPAM ) :?>
				<p><?php _e('Marked as spam'); ?></p>
			<?php endif; ?>
			
			<?php Plugins::act('comment_info', $comment); ?>

		</span>
		<span class="content edit-content area pct75"><?php echo htmlspecialchars( $comment->content ); ?></span>
	</div>
</div>

<?php 	endforeach; 
else : ?>
<div class="message none">
	<p><?php _e('No comments could be found to match the query criteria.'); ?></p>
</div>
<?php endif; ?>
