<?php include_once( 'header.php' ); ?>


<form method="post" action="<?php URL::out('admin', array( 'page' => 'logs' ) ); ?>" class="buttonform">


<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('0-0 of 0'); ?></span>
	<span class="search pct50"><input type="search" name='search' placeholder="<?php _e('Type and wait to search any log entry component'); ?>" autosave="habaricontent" results="10" value="<?php echo $search; ?>"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<?php $theme->display( 'timeline_items' )?>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div>

</div>

<div class="container">

	<div class="item clear">
		<div class="head clear">

			<span class="checkbox pct5">&nbsp;</span>
			<span class="time pct15"><?php _e('Date &amp; Time'); ?></span>
			<span class="user pct15"><?php _e('User'); ?></span>
			<span class="ip pct10"><?php _e('IP'); ?></span>
			<span class="module pct10"><?php _e('Module'); ?></span>
			<span class="type pct10"><?php _e('Type'); ?></span>
			<span class="severity pct5"><?php _e('Severity'); ?></span>
			<span class="message pct30"><?php _e('Message'); ?></span>

		</div>
	</div>


	<div class="item clear">
		<span class="pct5">&nbsp;</span>
		<span class="pct15"><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'pct90')); ?></span>
		<span class="pct15"><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('address', $addresses, $address, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'pct90')); ?></span>
		<span class="pct5"><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'pct90')); ?></span>
		<td align="right"><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></span>
	</div>
	
	<div class="manage logs">

	<?php $theme->display('logs_items'); ?>

	</div>

</div>


<div class="container transparent">

	<div class="item controls">
		<span class="pct25">
			<input type="checkbox">
			<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
		</span>
		<input type="button" value="<?php _e('Delete'); ?>" class="submitbutton">
	</div>

</div>


</form>

<script type="text/javascript">
liveSearch.search= function() {
	spinner.start();

	$.post(
		'<?php echo URL::get('admin_ajax', array('context' => 'logs')) ?>',
		'&search=' + liveSearch.input.val() + '&limit=20',
		function(json) {
			$('.logs').html(json.items);
			// we hide and show the timeline to fix a firefox display bug
			$('.years').html(json.timeline).hide();
			spinner.stop();
			itemManage.initItems();
			$('.years').show();
			timeline.reset();
			findChildren()
		},
		'json'
		);
};

timelineHandle.loupeUpdate = function(a,b,c) {
	spinner.start();

	var search_args= $('.search input').val();

	$.ajax({
		type: 'POST',
		url: "<?php echo URL::get('admin_ajax', array('context' => 'logs')); ?>",
		data: 'offset=' + (parseInt(c) - parseInt(b)) + '&limit=' + (1 + parseInt(b) - parseInt(a)) + '&search=' + search_args,
		dataType: 'json',
		success: function(json){
			$('.logs').html(json.items);
			spinner.stop();
			itemManage.initItems();
			$('.modulecore .item:first-child, ul li:first-child').addClass('first-child').show();
			$('.modulecore .item:last-child, ul li:last-child').addClass('last-child');
		}
	});
};
</script>

<?php include('footer.php'); ?>