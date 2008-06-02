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
	<h2><?php _e('Activity Logs'); ?></h2>

	<div class="handle">&nbsp;</div>

	<div class="optionscontent">
		<p>
			<label for="dummy" class="pct30"><?php _e('# of Log Entries'); ?></label>
			<select class="pct55">
				<option>10</option>
			</select>
		</p>

		<p class="buttons">
			<input type="submit" value="<?php _e('Submit'); ?>">
		</p>
	</div>
</div>
