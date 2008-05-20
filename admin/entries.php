<?php include('header.php');?>


<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-0 of 0</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10" value="<?php echo $search_args ?>"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false">Newer &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<div class="months">
				<?php $theme->display( 'timeline_items' )?>
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
		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>"></input>
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>"></input>
		<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>"></input>
		<span class="pct25">
			<input type="checkbox"></input>
			<span class="selectedtext minor none">None selected</span>
		</span>
		<select class="actiondropdown">
			<option value="1">Delete</option>
		</select>
		<input type="button" value="Submit" class="submitbutton">
	</div>

</div>

<script type="text/javascript">
liveSearch.search= function() {
	spinner.start();

	$.post(
		'<?php echo URL::get('admin_ajax', array('context' => 'entries')) ?>',
		'&search=' + liveSearch.input.val() + '&limit=20',
		function(json) {
			$('.entries').html(json.items);
			$('.years .months').html(json.timeline);
			spinner.stop();
			itemManage.initItems();
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
		url: "<?php echo URL::get('admin_ajax', array('context' => 'entries')); ?>",
		data: 'offset=' + (parseInt(c) - parseInt(b)) + '&limit=' + (1 + parseInt(b) - parseInt(a)) + '&search=' + search_args,
		dataType: 'json',
		success: function(json){
			$('.entries').html(json.items);
			spinner.stop();
			itemManage.initItems();
			$('.modulecore .item:first-child, ul li:first-child').addClass('first-child').show();
			$('.modulecore .item:last-child, ul li:last-child').addClass('last-child');
		}
	});
};
</script>


<?php include('footer.php');?>
