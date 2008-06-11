<?php include('header.php');?>


<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('0-0 of 0'); ?></span>
	<span class="search pct50">
		<input id="search" type="search" placeholder="<?php _e('Type and wait to search for any entry component'); ?>" autosave="habaricontent" results="10" value="<?php echo $search_args ?>">
	</span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>

	<div class="special_search pct100">
		<?php foreach($special_searches as $text => $term): ?>
		<a href="#<?php echo $term; ?>"><?php echo $text; ?></a>
		<?php endforeach; ?>
	</div>

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


<div class="container manage entries">

<?php $theme->display('posts_items'); ?>

</div>


<div class="container transparent">

	<div class="item controls">
		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
		<span class="pct25">
			<input type="checkbox">
			<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
		</span>
		<select class="actiondropdown">
			<option value="1"><?php _e('Delete'); ?></option>
		</select>
		<input type="button" value="<?php _e('Submit'); ?>" class="submitbutton">
	</div>

</div>

<script type="text/javascript">
liveSearch.search= function() {
	spinner.start();

	$.post(
		'<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>',
		'&search=' + liveSearch.input.val() + '&limit=20',
		function(json) {
			$('.entries').html(json.items);
			// we hide and show the timeline to fix a firefox display bug
			$('.years').html(json.timeline).hide();
			spinner.stop();
			itemManage.initItems();
			$('.years').show();
			timeline.reset();
			findChildren();
		},
		'json'
		);
};

timelineHandle.loupeUpdate = function(a,b,c) {
	spinner.start();

	var search_args= $('.search input').val();

	$.ajax({
		type: 'POST',
		url: "<?php echo URL::get('admin_ajax', array('context' => 'posts')); ?>",
		data: 'offset=' + (parseInt(c) - parseInt(b)) + '&limit=' + (1 + parseInt(b) - parseInt(a)) + '&search=' + search_args,
		dataType: 'json',
		success: function(json){
			$('.entries').html(json.items);
			spinner.stop();
			itemManage.initItems();
			findChildren();
		}
	});
};
</script>


<?php include('footer.php');?>
