<?php include('header.php');?>

<div class="container navigator">
	<input type="search" id="search" placeholder="<?php _e('Type and wait to search tags'); ?>" autosave="habaricontent" results="10">
</div>


<div id="tag_collection" class="container tags">
  <?php $theme->display( 'tag_collection' ); ?>
</div>


<div class="container tags transparent tags controls">
	<span class="checkboxandselected pct25">
		<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
	</span>

	<span class="renamecontrols pct50">
		<input type="text" class="renametext">
		<input type="button" value="<?php _e('Rename'); ?>" class="rename button">
	</span>

	<span class="or pct10"><?php _e('or'); ?></span>

	<span class="pct15 buttons"><input type="button" value="<?php _e('Delete Selected'); ?>" class="delete button"></span>

	<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" id="PasswordDigest" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
</div>

<script type="text/javascript">
tagManage.remove = function() {
	spinner.start();

	selected = $('.tags .tag.selected');
	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('You need to select some tags before you can delete them.'); ?>" );
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
			jQuery.each( msg, function( index, value ) {
				humanMsg.displayMsg( value );
			});
			tagManage.changeTag();
		},
		'json'
 	    );
};
tagManage.rename= function() {
	master = $('.tags.controls input.renametext').val().trim();

	// Unselect the master, if it's selected
	$('.tags .tag:contains(' + master + ')').each(function() {
		if ($(this).find('span').text() == master) {
			$(this).removeClass('selected');
		}
	})

	selected = $('.tags .tag.selected');

	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('You need to select some tags before you can rename them.'); ?>" );
		return;
	}
	else if ( master == '' ) {
		humanMsg.displayMsg( "<?php _e('You need to enter a new tag to rename tags.'); ?>" );
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
			$('#tag_collection').html(result['tags']);
			$('.tags .tag').click(function() {
					$(this).toggleClass('selected');
					tagManage.changeTag();
				}
			);
			tagManage.changeTag();
			jQuery.each( result['msg'], function( index, value ) {
				humanMsg.displayMsg( value );
			});
		},
		'json'
	);
};
</script>

<?php include('footer.php');?>
