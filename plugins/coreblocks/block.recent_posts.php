<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<ul id="recent_posts">
	<?php $posts = $content->recent_posts; foreach( $posts as $post): ?>
		<li>
			<a href="<?php echo $post->permalink; ?>">
				<?php echo $post->title; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>

