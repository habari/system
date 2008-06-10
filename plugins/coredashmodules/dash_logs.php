<div class="close">&nbsp;</div>

<div class="options">&nbsp;</div>

<div class="modulecore">
	<h2><a href="<?php Site::out_url('admin'); ?>/logs"><?php _e('Latest Log Activity'); ?></a></h2>
	<div class="handle">&nbsp;</div>
	<ul class="items">
		<?php foreach( $logs as $log ) { ?>
			<li class="item clear">
				<span class="date pct15 minor"><?php echo Format::nice_date( $log->timestamp, 'F j' ); ?></span>
				<span class="message pct85 minor"><?php echo $log->message; ?></span>
			</li>
		<?php } ?>
	</ul>
</div>

<div class="optionswindow"> 
	<h2><?php _e('Latest Log Activity'); ?></h2> 
	
	<div class="handle">&nbsp;</div> 

	<div class="optionscontent"> 
		<?php echo $logs_form; ?> 
	</div> 
</div>