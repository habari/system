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
	itemManage.updateURL = habari.url.ajaxUpdatePosts;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>";
	itemManage.fetchReplace = $('.posts');
</script>

<?php include('footer.php');?>
