	<ul class="items">
		<?php foreach( $logs as $log ) { ?>
			<li class="item clear">
				<span class="date pct15 minor"><?php $log->timestamp->out( 'F j' ); ?></span>
				<span class="message pct85 minor"><?php echo $log->message; ?></span>
			</li>
		<?php } ?>
	</ul>
