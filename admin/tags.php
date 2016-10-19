<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<!--<div class="instructions"><span>Click to select</span> &middot; <span>Double-click to open</span></div>-->

<div class="container transparent item controls">

	<?php echo $form->get(); ?>

</div>

<script type="text/javascript">
// Tell the item manager what to update and from where
$('#tag_collection').manager({updateURL: "<?php echo URL::get('admin_ajax', array('context' => 'tags')) ?>"});

// this is just visual effect stuff, the actual selecting and processing is done with FormUI
$('#tag_collection li.tag input').change(function() {
	var listitem = $(this).parent().parent();
	if(listitem.hasClass('selected')) {
		listitem.removeClass('selected');
		// remove item from selected list (again, visually, the actual removing is done by FormUI)
		var regex = /tag_[0-9]+/;
		var idclass = regex.exec(listitem.attr('class'));
		$('#selected_tags .' + idclass).remove();
	}
	else {
		listitem.addClass('selected');
		// keep all the properties and the input so we can click the item in both lists, but get rid of id to avoid conflicts
		var cloneli = listitem.clone();
		cloneli.find('input').removeAttr('id');
		cloneli.appendTo('#selected_tags');
	}
});

// we need additional code when all of the tags are selected with the FormUI aggregate control
// that is because programmatical changes of the checked state do not trigger events
$('.aggregate_ui[data-target=tags_selected_items]').change(function() {
	if($(this).attr("checked") == "checked") {
		$('#tag_collection li.tag input').each(function() {
			var listitem = $(this).parent().parent();
			if(!listitem.hasClass('selected')) {
				$(this).attr("checked", "checked");
				$(this).trigger("change");
			}
		});

	}
	else {
		$('#tag_collection li.tag input').each(function() {
			var listitem = $(this).parent().parent();
			if(listitem.hasClass('selected')) {
				$(this).removeAttr("checked");
				$(this).trigger("change");
			}
		});
	}
});

//legacy code
/*
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
*/
</script>

<?php include('footer.php');?>
