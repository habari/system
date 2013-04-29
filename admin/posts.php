<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include('header.php');?>

<div class="container transparent item controls">

	<?php

	$search = FormControlFacet::create('search');
	echo $search->pre_out();
	echo $search->get($theme);

	$aggregate = FormControlAggregate::create('selected_posts')->set_selector('.post_item')->label('None Selected');
	echo $aggregate->pre_out();
	echo $aggregate->get($theme);


	$page_actions = array(
		'delete' => array('href' => '#delete_selected', 'onclick' => 'itemManage.update(\'delete\');return false;', 'title' => _t('Delete Selected'), 'caption' => _t('Delete Selected') ),
	);
	$page_actions = Plugins::filter('posts_manage_actions', $page_actions);
	$dbtn = FormControlDropbutton::create('page_actions')->set_actions($page_actions);
	echo $dbtn->pre_out();
	echo $dbtn->get($theme);
	?>

</div>


<div class="container posts">

<?php $theme->display('posts_items'); ?>

</div>

<script type="text/javascript">
	itemManage.updateURL = habari.url.ajaxUpdatePosts;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>";
	itemManage.fetchReplace = $('.posts');
</script>

<?php include('footer.php');?>
