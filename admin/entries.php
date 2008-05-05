<?php include('header.php');?>



<div class="container navigator">
	<span class="older pct10"><a href="#">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-20 of 480</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#">Newer &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<div class="months">
				<?php foreach($monthposts as $pdata): ?>
				<div><span style="width: <?php echo $pdata->ct; ?>px"><?php echo date('M', mktime(0, 0, 0, $pdata->month)) ?></span></div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div>

</div>


<div class="container manage entries">

<?php $theme->display('entries_items'); ?>

</div>


<div class="container transparent">

	<div class="item controls">
		<span class="pct25">
			<input type="checkbox">
			<span class="selectedtext minor none">None selected</span>
		</span>
		<select class="actiondropdown">
			<option>Delete</option>
		</select>
		<input type="button" value="Submit" class="submitbutton">
	</div>

</div>

<script type="text/javascript">
timelineHandle.loupeUpdate = function(a,b,c) {
	spinner.start();
	$.ajax({
		type: "POST",
		url: "<?php echo URL::get('admin_ajax', array('context' => 'entries')); ?>",
		data: "offset=" + (parseInt(c) - parseInt(b)) + "&limit=" + (parseInt(b) - parseInt(a)),
		dataType: 'json',
		success: function(json){
			$('.entries').html(json.items);
			spinner.stop();
		}
	});
};
</script>


<?php include('footer.php');?>