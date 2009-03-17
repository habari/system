	<ul class="items">

		<?php foreach ( $latestcomments_posts as $post ): ?>
		<li class="item clear">
			<span class="comments pct15" style="float: right;"><a href="<?php echo $post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $post->comments->approved->comments->count), $post->comments->approved->comments->count); ?>"><?php echo $post->comments->approved->comments->count; ?></a></span>
			<span class="titleanddate pct85"><a href="<?php echo $post->permalink; ?>" class="title"><?php echo $post->title; ?></a> <?php $post->pubdate->out( 'M j' ); ?></span>
			<ul class="commentauthors pct85 minor">
				<?php
				$comment_count = 0;
				foreach( $latestcomments[$post->id] as $comment):
					$comment_count++;
					$opa = 'opa' . (100 - $comment_count * 15);
				?>
				<li><a href="<?php echo $comment->post->permalink; ?>#comment-<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), $comment->date->get( 'g:m a \o\n F jS, Y' ) ); ?>" class="<?php echo $opa; ?>"><?php echo $comment->name; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php endforeach; ?>

	</ul>
