<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<!--<div class="instructions"><span>Click to select</span> &middot; <span>Double-click to open</span></div>-->

<div class="container transparent item controls">

	<?php echo $form->get(); ?>

</div>

<script type="text/javascript">
// Tell the item manager what to update and from where
$('#tag_collection').manager({updateURL: "<?php echo URL::get('admin_ajax', array('context' => 'tags')) ?>", after_update: "visual_hooks"});

// this is just visual effect stuff, the actual selecting and processing is done with FormUI
function visual_hooks() {
	// Cleanup as we call this after filtering
	// @TODO Do not lose selection when filtering
	$("#selected_tags li").remove();
	$("#tags_selected_items").val("")

	$('#tag_collection li.tag input').change(function() {
		var listitem = $(this).parent();
		if(!$(this).attr('checked')) {
			// remove item from selected list (again, visually, the actual removing is done by FormUI)
			var regex = /tag_[0-9]+/;
			var idclass = regex.exec(listitem.attr('class'));
			$('#selected_tags .' + idclass).remove();
		}
		else {
			// keep all the properties and the input so we can click the item in both lists, but get rid of id to avoid conflicts
			var cloneli = listitem.clone();
			cloneli.find('input').removeAttr('id');
			cloneli.appendTo('#selected_tags');
		}
	});
}
visual_hooks();

// we need additional code when all of the tags are selected with the FormUI aggregate control
// that is because programmatical changes of the checked state do not trigger events
$('.aggregate_ui[data-target=tags_selected_items]').change(function() {
	if($(this).attr("checked") == "checked") {
		$('#tag_collection li.tag input').each(function() {
			if(!$(this).attr('checked')) {
				$(this).attr("checked", "checked");
				$(this).trigger("change");
			}
		});

	}
	else {
		deselect_all();
	}
});

// Helper function to remove all selection
// Used when searching to avoid hidden selected items
function deselect_all()
{
	$('#tag_collection li.tag input').each(function() {
		if($(this).attr('checked')) {
			$(this).removeAttr("checked");
			$(this).trigger("change");
		}
	});
}
</script>

<?php include('footer.php');?>
