<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
	<ul class="items">
		<li class="item clear">
			<span class="pct70 minor"><?php _e( 'Category' ); ?></span>
			<span class ="pct15"><?php _e( 'Yours' ); ?></span>
			<span class="comments pct15" style="float: right;"><?php _e( 'Site' ) ?></span>
		</li>
		<?php foreach( $content->messages as $message ) : ?>
		<li class="item clear">
			<span class="pct70 minor"><?php echo $message['label']; ?></span>
			<span class ="pct15"><?php echo $message['user_count'] ?></span>
			<span class="comments pct15" style="float: right;"><?php echo $message['site_count']; ?></span>
		</li>
		<?php endforeach; ?>
	</ul>
