<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include('header.php');?>

<div class="container transparent item controls">

	<?php

	echo $form->get();
	?>

</div>

<div class="container main posts manage">

	<?php $theme->display('posts_items'); ?>

</div>

<script type="text/javascript">
	$('.posts').manager({after_update: "update_querystring", updateURL: "<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>"});
	$('.posts').manager('nothing').data('page', <?php echo $pagenr; ?>);

	function update_querystring() {
		// Grab querystring compiled from the faceted search. If it exists, update the window URL
		var querystring = $('.posts').manager('nothing').data('querystring');
		if(querystring != null) {
			window.history.replaceState(document.title, document.title, window.location.pathname + "?" + querystring);
		}
		else {
			querystring = window.location.href.replace(window.location.origin + window.location.pathname + "?", "");
		}

		// Modify the page navigation links according to the current page
		// This is a bit tricky as we need to preserve the query string and need to figure out if the page is already in it
		var page = parseInt($('.posts').manager('nothing').data('page'));
		var pageparam = querystring.match(/page\=([0-9]+)/);
		if(pageparam !== null) {
			$("#nav_next").attr("href", window.location.pathname + "?" + querystring.replace(pageparam[0], "page=" + (page + 1)));
			$("#nav_prev").attr("href", window.location.pathname + "?" + querystring.replace(pageparam[0], "page=" + (page - 1)));
		}
		else {
			$("#nav_next").attr("href", window.location.pathname + "?" + querystring + "&page=" + (page + 1));
			$("#nav_prev").attr("href", window.location.pathname + "?" + querystring + "&page=" + (page - 1));
		}

		// Very primitive but for now sufficient way to hide the "previous" link if there is no previuos page
		if(page == 1) {
			$("#nav_prev").hide();
		}
		else {
			$("#nav_prev").show();
		}
	}
	update_querystring();
</script>

<?php include('footer.php');?>
