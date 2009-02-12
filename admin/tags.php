<?php include('header.php');?>

<div class="container navigator">
	<input type="search" id="search" placeholder="<?php _e('Type and wait to search tags'); ?>" autosave="habaricontent" results="10">
</div>

<!--<div class="instructions"><span>Click to select</span> &middot; <span>Double-click to open</span></div>-->

<div id="tag_collection" class="container items">
	<?php $theme->display( 'tag_collection' ); ?>
</div>


<div class="container transparent item controls">
	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

	<span class="checkboxandselected pct20">
		<input type="checkbox" id="master_checkbox" name="master_checkbox">
		<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
	</span>

	<span class="renamecontrols pct35"><input type="text" class="renametext"></span>

	<span class="pct15 buttons"><input type="button" value="<?php _e('Rename'); ?>" class="rename button"></span>

	<span class="or pct10"><?php _e('or'); ?></span>

	<span class="pct15 buttons"><input type="button" value="<?php _e('Delete Selected'); ?>" class="delete button"></span>

	<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" id="PasswordDigest" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
</div>

<script type="text/javascript">
itemManage.update = function( action, id ) {
	spinner.start();

	selected = $('.tag.selected');
	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('Error: No tags selected.'); ?>" );
		return;
	}
	var query = {}

	selected.each(function() {
		query[$(this).attr('id')] = 1;
	});

	query['action'] = 'delete';
	query['timestamp'] = $('input#timestamp').attr('value');
	query['nonce'] = $('input#nonce').attr('value');
	query['digest'] = $('input#PasswordDigest').attr('value');

	$.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
        query,
        function(msg) {
			spinner.stop();
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			selected.remove();
			itemManage.selected = {};
			itemManage.changeItem();
			itemManage.initItems();
			jQuery.each( msg, function( index, value ) {
				humanMsg.displayMsg( value );
			});
		},
		'json'
	);
};

itemManage.rename = function() {
	master = $('.controls input.renametext').val();

	// Unselect the master, if it's selected
	$('.tag:contains(' + master + ')').each(function() {
		if ($(this).find('span').text() == master) {
			$(this).removeClass('selected');
		}
	})

	selected = $('.tag.selected');

	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('Error: No tags selected.'); ?>" );
		return;
	}
	else if ( master == '' ) {
		humanMsg.displayMsg( "<?php _e('Error: New name not specified.'); ?>" );
		return;
	}
	var query = {}

	spinner.start();

	selected.each(function() {
		query[$(this).attr('id')] = 1;
	});

	query['master'] = master;
	query['action'] = 'rename';
	query['timestamp'] = $('input#timestamp').attr('value');
	query['nonce'] = $('input#nonce').attr('value');
	query['digest'] = $('input#PasswordDigest').attr('value');
	$.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
		query,
		function(result) {
			spinner.stop();
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			$('.controls input.renametext').val('');
			$('#tag_collection').html(result['tags']);
			jQuery.each( result['msg'], function( index, value ) {
				humanMsg.displayMsg( value );
			});

			itemManage.selected = {};

			itemManage.initItems();
		},
		'json'
	);
};
</script>

<?php include('footer.php');?>
