<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>

<div class="container transparent item controls">

	<?php

	// $search = FormControlFacet::create('search');
	// echo $search->pre_out();
	// echo $search->get($theme);

	$aggregate = FormControlAggregate::create('selected_comments')->set_selector('.comment_checkbox')->label('None Selected');
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
	Plugins::act('comments_manage_actions', $page_actions);
	echo $page_actions->pre_out();
	echo $page_actions->get($theme);
	?>

</div>

<div id="comments" class="container main manage comments">

<?php $theme->display('comments_items'); ?>

</div>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateComment;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'comments')) ?>";
itemManage.fetchReplace = $('#comments');

</script>


<?php include('footer.php'); ?>
