<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
	<ul class="items">
		<?php foreach( $type_messages as $message ) : ?>
		<li class="item clear">
			<span class="pct70 minor"><?php echo $message['label']; ?></span>
			<span class="comments pct15" style="float: right;"><?php echo $message['count']; ?></span>
		</li>
		<?php endforeach; ?>
	</ul>
