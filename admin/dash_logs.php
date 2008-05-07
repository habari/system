<div class="options">&nbsp;</div>
<div class="modulecore">
	<h2>Activity Logs</h2>
	<div class="handle">&nbsp;</div>
	<ul class="items">
		<?php foreach( eventlog::get( array( 'limit' => 10 ) ) as $log ) { ?>
			<li class="item clear">
				<span class="date pct15 minor"><?php echo Format::nice_date( $log->timestamp, 'F j, Y' ); ?></span>
				<span class="title pct15"><?php echo $log->type; ?></span>
				<span class="pct70"><?php echo $log->message; ?></span>
			</li>
		<?php } ?>
	</ul>
</div>
<div class="optionswindow">
	<h2>Activity Logs</h2>

	<div class="handle">&nbsp;</div>

	<div class="optionscontent">
		<p>
			<label for="dummy" class="pct30"># of Log Entries</label>
			<select class="pct55">
				<option>10</option>
			</select>
		</p>

		<p class="buttons">
			<input type="submit" value="Submit">
		</p>
	</div>
</div>