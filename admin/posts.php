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


	$page_actions = FormControlDropbutton::create('page_actions');
	$page_actions->append(
		FormControlSubmit::create('delete')
			->set_caption(_t('Delete Selected'))
			->set_properties(array(
				'onclick' => 'itemManage.update(\'delete\');return false;',
				'title' => _t('Delete Selected'),
			))
	);
	Plugins::act('posts_manage_actions', $page_actions);
	echo $page_actions->pre_out();
	echo $page_actions->get($theme);
	?>

</div>

<table class="container main" id="post_data">
	<tbody class="manage_posts">

	<?php $theme->display('posts_items'); ?>

	</tbody>
</table>

<script type="text/javascript">
	itemManage.updateURL = habari.url.ajaxUpdatePosts;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>";
	itemManage.fetchReplace = $('.posts');
</script>

<?php include('footer.php');?>
