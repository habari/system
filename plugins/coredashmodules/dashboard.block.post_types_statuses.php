<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
?>
	<ul class="items">
		<li class="item">
			<span><?php _e( 'Category' ); ?></span>
			<span><?php _e( 'Yours' ); ?></span>
			<span class="comments"><?php _e( 'Site' ) ?></span>
		</li>
		<?php foreach( $content->messages as $message ) : ?>
		<li class="item">
			<span><?php echo $message['label']; ?></span>
			<span><?php echo $message['user_count'] ?></span>
			<span class="comments"><?php echo $message['site_count']; ?></span>
		</li>
		<?php endforeach; ?>
	</ul>
