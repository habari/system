<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<?php include( 'navigator.php' ); ?>

<div class="container transparent item controls">

	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">
	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox" name="master_checkbox">
		<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
	</span>
	<ul class="dropbutton">
		<?php $page_actions = array(
			'delete' => array('action' => 'itemManage.update(\'delete\');return false;', 'title' => _t('Delete Selected'), 'label' => _t('Delete Selected') ),
		);
		$page_actions = Plugins::filter('posts_manage_actions', $page_actions);
		foreach( $page_actions as $page_action ) : ?>
			<li><a href="*" onclick="<?php echo $page_action['action']; ?>" title="<?php echo $page_action['title']; ?>"><?php echo $page_action['label']; ?></a></li>
		<?php endforeach; ?>
	</ul>
	
</div>


<div class="container posts">

<?php $theme->display('posts_items'); ?>

</div>


<div class="container transparent item controls">

	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox_2" name="master_checkbox_2">
		<label class="selectedtext minor none" for="master_checkbox_2"><?php _e('None selected'); ?></label>
	</span>
	<ul class="dropbutton">
		<?php $page_actions = array(
			'delete' => array('action' => 'itemManage.update(\'delete\');return false;', 'title' => _t('Delete Selected'), 'label' => _t('Delete Selected') ),
		);
		$page_actions = Plugins::filter('posts_manage_actions', $page_actions);
		foreach( $page_actions as $page_action ) : ?>
			<li><a href="*" onclick="<?php echo $page_action['action']; ?>" title="<?php echo $page_action['title']; ?>"><?php echo $page_action['label']; ?></a></li>
		<?php endforeach; ?>
	</ul>

</div>

<script type="text/javascript">
	itemManage.updateURL = habari.url.ajaxUpdatePosts;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'posts')) ?>";
	itemManage.fetchReplace = $('.posts');
</script>

<?php include('footer.php');?>
