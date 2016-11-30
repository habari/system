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

	function update_querystring() {
		window.history.replaceState(document.title, document.title, window.location.pathname + "?" + $('.posts').manager('nothing').data('querystring'));
	}
</script>

<?php include('footer.php');?>
