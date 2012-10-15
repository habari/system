<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<div class="container navigator">
	<span class="search pct100"><input type="search" id="search" placeholder="<?php _e('Type and wait to search tags'); ?>" autofocus="autofocus"></span>
</div>

<!--<div class="instructions"><span>Click to select</span> &middot; <span>Double-click to open</span></div>-->

<ul id="tag_collection" class="container items">
	<?php $theme->display( 'tag_collection' ); ?>
</ul>


<div class="container transparent item controls">
	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">

	<span class="checkboxandselected pct20">
		<input type="checkbox" id="master_checkbox" name="master_checkbox">
		<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
	</span>

	<span class="renamecontrols pct35"><input type="text" class="renametext"></span>

	<span class="pct15 buttons"><input type="button" value="<?php _e('Rename'); ?>" class="rename button"></span>

	<span class="or pct10"><?php _e('or'); ?></span>

	<span class="pct15 buttons"><input type="button" value="<?php _e('Delete Selected'); ?>" class="delete button"></span>
</div>

<script type="text/javascript">
itemManage.fetch = function(offset, limit, resetTimeline, silent) {
	query = {};
	query['timestamp'] = $('input#timestamp').attr('value');
	query['nonce'] = $('input#nonce').attr('value');
	query['digest'] = $('input#password_digest').attr('value');
	query['search'] = liveSearch.getSearchText();

	habari_ajax.get(
		"<?php echo URL::get('admin_ajax', array('context' => 'get_tags')); ?>",
		query,
		function(result) {
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			$('#tag_collection').html(result['data']);
			itemManage.selected = {};
			itemManage.initItems();
		}
	);
};

itemManage.update = function( action, id ) {
	spinner.start();

	selected = $('.tag.selected');
	if ( selected.length == 0 ) {
		human_msg.display_msg( "<?php _e('Error: No tags selected.'); ?>" );
		spinner.stop();
		return;
	}
	var query = {}

	selected.each(function() {
		query[$(this).attr('id')] = 1;
	});

	query['action'] = 'delete';
	query['timestamp'] = $('input#timestamp').attr('value');
	query['nonce'] = $('input#nonce').attr('value');
	query['digest'] = $('input#password_digest').attr('value');

	habari_ajax.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
		query,
		function(result) {
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			$('#tag_collection').html(result);
			itemManage.selected = {};
			itemManage.initItems();
		}
	);
};

itemManage.rename = function() {
	var master = $('.controls input.renametext').val();

	// Unselect the master, if it's selected
	if ( master ) {
		$('.tag:contains(' + master + ')').each(function() {
			if ($(this).find('span').text() == master) {
				$(this).removeClass('selected');
			}
		});
	}

	var selected = $('.tag.selected');

	if ( selected.length == 0 ) {
		human_msg.display_msg( "<?php _e('Error: No tags selected.'); ?>" );
		return;
	}
	else if ( master == '' ) {
		human_msg.display_msg( "<?php _e('Error: New name not specified.'); ?>" );
		return;
	}
	else if ( $.trim( master ) == '' ) {
		human_msg.display_msg( "<?php _e("Error: New name can't be just whitespace."); ?>" );
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
	query['digest'] = $('input#password_digest').attr('value');
	habari_ajax.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
		query,
		function(result) {
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			$('.controls input.renametext').val('').blur();
			$('#tag_collection').html(result);

			itemManage.selected = {};
			itemManage.initItems();
		}
	);
};

// overload changeItem()
var parentChangeItem = itemManage.changeItem;

itemManage.changeItem = function() {
	parentChangeItem();
	
	var checked = $('.item:not(.ignore) .checkbox input[type=checkbox]:checked');
	
	if ( !checked.length ) {
		$(".controls input.rename").val("<?php _e('Rename'); ?>");
		$(".controls input.renametext").blur();
	} else if ( checked.length == 1 ) {
		$(".controls input.rename").val("<?php _e('Rename'); ?>");
		$(".controls input.renametext").focus();
	} else {
		$(".controls input.rename").val("<?php _e('Merge'); ?>");
		$(".controls input.renametext").focus();
	}
}
</script>

<?php include('footer.php');?>
