<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
?>
	<ul class="items">
		<?php foreach( $content->logs as $log ) { ?>
			<li class="item clear">
				<span class="date pct15 minor"><?php /* @locale Date formats according to http://php.net/manual/en/function.date.php */ $log->timestamp->out( _t( 'M j' ) ); ?></span>
				<span class="message pct85 minor"><?php echo Utils::htmlspecialchars( $log->message ); ?></span>
			</li>
		<?php } ?>
	</ul>
