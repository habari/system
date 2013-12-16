<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php if ( count($comments) != 0 ) : ?>
<ul>
<?php foreach ( $comments as $comment ) : ?>
<li class="comment <?php echo strtolower( $comment->statusname ); ?> manage_item" id="comment_<?php echo $comment->id; ?>" style="<?php echo Plugins::filter('comment_style', '', $comment); ?>">
	<div class="head">
		<span class="checkbox">
			<input class="comment_checkbox" type="checkbox" name="checkbox_ids[<?php echo $comment->id; ?>]" value="<?php echo $comment->id; ?>">
		</span>
		<div class="comment-info">
			<span class="comment-status"><?php _e($comment->statusname); ?></span>
			<span class="comment-type"><?php echo Plugins::filter( 'comment_type_display', $comment->typename, 'singular' ); ?></span>
		</div>
		<span class="title comment_post">
			<?php _e('in'); ?> <a href="<?php echo $comment->post->permalink ?>#comment-<?php echo $comment->id; ?>" title="<?php _e( 'Go to %s', array( $comment->post->title ) ); ?>"><?php echo $comment->post->title; ?></a>
		</span>
		<div class="actions">
			<?php
			echo $comment->menu->pre_out();
			echo $comment->menu->get($theme);
			?>
		</div>
	</div>

	<div>
		<ul class="meta">
			<li>
				<span class="date"><a href="<?php URL::out('admin', array('page' => 'comments', 'status' => $comment->status, 'year' => $comment->date->year, 'month' => $comment->date->mon )); ?>" title="<?php _e('Search for other comments from %s', array($comment->date->format( 'M, Y' ) ) ); ?>"><?php $comment->date->out( DateTime::get_default_date_format() ); ?></a></span>
				<span class="time"><?php _e('at'); ?> <?php $comment->date->out( DateTime::get_default_time_format() );?></span>
			</li>
			<li class="comment_author"><?php echo Utils::htmlspecialchars( $comment->name ); ?></li>
			<?php if ( $comment->url != '' ) {
					echo '<li><a class="url" href="' . $comment->url . '">' . $comment->url . '</a></li>'."\r\n";
				}
				else {
					echo '<li class="empty">' . _t( 'no url given' ) . '</li>';
				} ?>
			<?php if ( $comment->email != '' ): ?>
				<li><a class="email" href="mailto:<?php echo $comment->email; ?>"><?php echo $comment->email; ?></a></li>
			<?php endif ?>
			<?php if ( $comment->ip ): ?>
				<li><?php echo $comment->ip; ?></li>
			<?php endif; ?>
		</ul>
		<span class="excerpt"><?php
			if ( MultiByte::valid_data( $comment->content ) ):
				echo MultiByte::substr( strip_tags( $comment->content ), 0, 250);
				?>&hellip;<?php
			else:
				_e('this feedback contains text in an invalid encoding');
			endif; ?>
		</span>
	</div>
</li>
<?php endforeach; ?>
</ul>
<?php else : ?>
<div class="message none">
	<p><?php _e('No comments could be found to match the query criteria.'); ?></p>
</div>
<?php endif; ?>
