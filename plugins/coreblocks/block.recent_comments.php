<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<ul id="recent_comments">
	<?php $comments = $content->recent_comments; foreach( $comments as $comment): ?>
		<li>
<?php
		if ( $comment->url ) {
			$name_html = sprintf('<a class="username" href="%s">%s</a>',
				$comment->url, $comment->name);
		}
		else {
			$name_html = sprintf('<span class="username">%s</span>',
				$comment->name);
		}
		$post_html = sprintf('<a href="%s">%s</a>',
			$comment->post->permalink, $comment->post->title);
		// @locale An item in the "Comments" menu list showing that a user commented on a post ("user on post")
		_e('%1$s on %2$s', array( $name_html, $post_html ));
?>
		</li>
	<?php endforeach; ?>
</ul>
