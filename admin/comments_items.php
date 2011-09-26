<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php if ( count($comments) != 0 ) :
	foreach ( $comments as $comment ) : ?>

<div class="item clear <?php echo strtolower( $comment->statusname ); ?>" id="comment_<?php echo $comment->id; ?>" style="<?php echo Plugins::filter('comment_style', '', $comment); ?>">
	<div class="head clear">
		<span class="checkbox title pct5">
			<input type="checkbox" class="checkbox" name="comment_ids[<?php echo $comment->id; ?>]" id="comments_ids[<?php echo $comment->id; ?>]" value="1">
		</span>
		<span class="checkbox title pct20">
			<?php if ( $comment->url != '' ): ?>
			<a href="#" class="author" title="<?php echo Utils::htmlspecialchars( $comment->name ); ?>"><?php echo Utils::htmlspecialchars( $comment->name ); ?></a>
			<?php else: ?>
			<?php echo Utils::htmlspecialchars( $comment->name ); ?>
			<?php endif; ?>
		</span>
		<span class="title pct35"><span class="dim"><?php _e('in'); ?> '</span><a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>" title="<?php _e( 'Go to %s', array( $comment->post->title ) ); ?>"><?php echo $comment->post->title; ?></a><span class="dim">'</span></span>
		<span class="date pct15"><span class="dim"><?php _e('on'); ?></span> <a href="<?php URL::out('admin', array('page' => 'comments', 'status' => $comment->status, 'year' => $comment->date->year, 'month' => $comment->date->mon )); ?>" title="<?php _e('Search for other comments from %s', array($comment->date->format( 'M, Y' ) ) ); ?>"><?php $comment->date->out( HabariDateTime::get_default_date_format() ); ?></a></span>
		<span class="time pct10 dim"><?php _e('at'); ?> <span><?php $comment->date->out( HabariDateTime::get_default_time_format() );?></span></span>

		<ul class="dropbutton">
			<?php
			foreach($comment->menu as $act_id => $action):
				$url = str_replace('__commentid__', $comment->id, $action['url']);
			?>
			<li class="<?php echo $act_id; if (isset($action['nodisplay']) && $action['nodisplay'] == true) { echo ' nodisplay'; } ?>"><a href="<?php echo $url; ?>" title="<?php echo $action['title']; ?>"><?php echo $action['label']; ?></a></li>
			<?php endforeach;?>
			<?php $theme->admin_comment_actions($comment); ?>
		</ul>
	</div>

	<div class="infoandcontent clear">
		<div class="authorinfo pct25 minor">
			<ul>
				<?php if ( $comment->url != '' ) {
						echo '<li><a class="url" href="' . $comment->url . '">' . $comment->url . '</a></li>'."\r\n";
					}
					else {
						echo '<li class="empty">' . _t( 'no url given' ) . '</li>';
					} ?>
				<?php if ( $comment->email != '' ) {
						echo '<li><a class="email" href="mailto:' . $comment->email . '">' . $comment->email . '</a></li>'."\r\n";
					}
					else {
						echo '<li class="empty">' . _t( 'no email provided' ) . '</li>';
					} ?>
				<?php if ( $comment->ip ): ?>
					<li><?php echo $comment->ip; ?></li>
				<?php endif; ?>
			</ul>
			<?php if ( $comment->status == Comment::STATUS_SPAM ) :?>
				<p><?php _e('Marked as spam'); ?></p>
			<?php endif; ?>

			<?php Plugins::act('comment_info', $comment); ?>

			<p class="comment-type"><?php echo Plugins::filter( 'comment_type_display', $comment->typename, 'singular' ); ?></p>
		</div>
		<span class="content pct75"><?php
			if ( MultiByte::valid_data( $comment->content ) ) {
				echo nl2br( Utils::htmlspecialchars( $comment->content ) );
			}
			else {
				_e('this post contains text in an invalid encoding');
			}
		?></span>
	</div>
</div>

<?php 	endforeach;
else : ?>
<div class="message none">
	<p><?php _e('No comments could be found to match the query criteria.'); ?></p>
</div>
<?php endif; ?>
