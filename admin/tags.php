<?php include('header.php');?>

<!-- TODO: implement the tags loupe -->
<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('0-0 of 0'); ?></span>
	<span class="search pct50"><input type="search" placeholder="<?php _e('Type and wait to search tags'); ?>" autosave="habaricontent" results="10"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>
</div>


<div id="tag_collection" class="container tags">
  <?php $theme->display( 'tag_collection' ); ?>
</div>


<div class="container tags transparent">
	<div class="tags controls">
		<span class="checkboxandselected pct15">
			<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
		</span>
		<span><input type="button" value="<?php _e('Delete'); ?>" class="deletebutton"></span>
		<span class="or pct5"><?php _e('or'); ?></span>
		<span class="renamecontrols">
			<input type="text" class="renametext">
			<input type="button" value="<?php _e('Rename'); ?>" class="renamebutton">
		</span>
		<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" id="PasswordDigest" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
	</div>
</div>

<script type="text/javascript">
tagManage.remove= function() {
	spinner.start();

	selected= $('.tags .tag.selected');
	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('You need to select some tags before you can delete them.'); ?>" );
		return;
	}
	var query= {}

	selected.each(function() {
		query[$(this).attr('id')]= 1;
	});

	query['action']= 'delete';
	query['timestamp']= $('input#timestamp').attr('value');
	query['nonce']= $('input#nonce').attr('value');
	query['digest']= $('input#PasswordDigest').attr('value');
	$.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
		query,
		function(msg) {
			spinner.stop();
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			selected.remove();
			humanMsg.displayMsg(msg);
			tagManage.changeTag();
		},
		'json'
	);
};
tagManage.rename= function() {
	master= $('.tags.controls input.renametext').val().trim();

	// Unselect the master, if it's selected
	$('.tags .tag:contains(' + master + ')').each(function() {
		if ($(this).find('span').text() == master) {
			$(this).removeClass('selected');
		}
	})

	selected= $('.tags .tag.selected');

	if ( selected.length == 0 ) {
		humanMsg.displayMsg( "<?php _e('You need to select some tags before you can rename them.'); ?>" );
		return;
	}
	else if ( master == '' ) {
		humanMsg.displayMsg( "<?php _e('You need to enter a new tag to rename tags.'); ?>" );
		return;
	}
	var query= {}

	spinner.start();

	selected.each(function() {
		query[$(this).attr('id')]= 1;
	});

	query['master']= master;
	query['action']= 'rename';
	query['timestamp']= $('input#timestamp').attr('value');
	query['nonce']= $('input#nonce').attr('value');
	query['digest']= $('input#PasswordDigest').attr('value');
	$.post(
		"<?php echo URL::get('admin_ajax', array('context' => 'tags')); ?>",
		query,
		function(data) {
			spinner.stop();
			//TODO When there's a loupe, update it
			//timelineHandle.updateLoupeInfo();
			$('#tag_collection').html(data['tags']);
			$('.tags .tag').click(function() {
					$(this).toggleClass('selected');
					tagManage.changeTag();
				}
			);
			tagManage.changeTag();
			humanMsg.displayMsg(data['msg']);
		},
		'json'
	);
};
</script>

<?php include('footer.php');?>
