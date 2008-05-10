<?php include('header.php'); ?>

<div class="container navigator">
	<span class="older pct10"><a href="#">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-0 of 0</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#">Newer &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<div class="months">
				<?php foreach($monthcomments as $cdata): ?>
				<div><span style="width: <?php echo $cdata->ct; ?>px"><?php echo date('M', mktime(0, 0, 0, $cdata->month)) ?></span></div>
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

<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'comments', 'search_status' => $search_status ) ); ?>">
	<input type="hidden" name="search" value="<?php echo $search; ?>">
	<input type="hidden" name="limit" value="<?php echo $limit; ?>">
	<input type="hidden" name="index" value="<?php echo $index; ?>">
	<input type="hidden" name="search_status" value="<?php echo $search_status; ?>">
	<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" id="PasswordDigest" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

	<div class="container transparent">

		<div class="item controls">
			<span class="checkboxandselected pct25">
				<input type="checkbox"></input>
				<span class="selectedtext minor none">None selected</span>
			</span>
			<span class="buttons">
				<input type="submit" name="do_delete" value="Delete" class="deletebutton"></input>
				<input type="submit" name="do_spam" value="Spam" class="spambutton"></input>
				<input type="submit" name="do_unapprove" value="Unapprove" class="spambutton"></input>
				<input type="submit" name="do_approve" value="Approve" class="approvebutton"></input>
			</span>
		</div>
	</div>

<div id="comments" class="container manage">

<?php $theme->display('comments_items'); ?>

</div>


<div class="container transparent">

	<div class="item controls">
		<span class="checkboxandselected pct25">
			<input type="checkbox"></input>
			<span class="selectedtext minor none">None selected</span>
		</span>
		<span class="buttons">
			<input type="submit" name="do_delete" value="Delete" class="deletebutton"></input>
			<input type="submit" name="do_spam" value="Spam" class="spambutton"></input>
			<input type="submit" name="do_unapprove" value="Unapprove" class="spambutton"></input>
			<input type="submit" name="do_approve" value="Approve" class="approvebutton"></input>
		</span>
	</div>
</div>

</form>

<script type="text/javascript">
timelineHandle.loupeUpdate = function(a,b,c) {
	spinner.start();

	$.ajax({
		type: "POST",
		url: "<?php echo URL::get('admin_ajax', array('context' => 'comments')); ?>",
		data: "offset=" + (parseInt(c) - parseInt(b)) + "&limit=" + (parseInt(b) - parseInt(a)) +
			<?php
				$vars= Controller::get_handler_vars();
				$out= '';
				$keys= array_keys($vars);
				foreach($keys as $key) {
					$out .= "&$key=$vars[$key]";
				}
				echo '"' . $out . '"';
			?>,
		dataType: 'json',
		success: function(json){
			$('#comments').html(json.items);
			spinner.stop();
			itemManage.initItems();
			$('.modulecore .item:first-child, ul li:first-child').addClass('first-child').show();
			$('.modulecore .item:last-child, ul li:last-child').addClass('last-child');
		}
	});
};

itemManage.update = function( id, action) {
	spinner.start();
	var query= {};
	query['id']= id;
	query['action']= action;
	query['timestamp']= $('input#timestamp').attr('value');
	query['nonce']= $('input#nonce').attr('value');
	query['digest']= $('input#PasswordDigest').attr('value');

	$.post(
		habari.url.ajaxUpdateComment,
		query,
		function(result) {
			spinner.stop();
			timelineHandle.updateLoupeInfo();
			humanMsg.displayMsg(result);
		},
		'json'
	);
}

</script>


<?php include('footer.php'); ?>
