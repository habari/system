<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include_once( 'header.php' ); ?>

<?php include( 'navigator.php' ); ?>

<form method="post" action="<?php URL::out('admin', array( 'page' => 'logs' ) ); ?>" class="buttonform">

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
			'purge' => array('action' => 'itemManage.update(\'purge\');return false;', 'title' => _t('Purge Logs'), 'label' => _t('Purge Logs') ),
		);
		$page_actions = Plugins::filter('logs_manage_actions', $page_actions);
		foreach( $page_actions as $page_action ) : ?>
			<li><a href="*" onclick="<?php echo $page_action['action']; ?>" title="<?php echo $page_action['title']; ?>"><?php echo $page_action['label']; ?></a></li>
		<?php endforeach; ?>
	</ul>
	
</div>

<div class="container">

	<div class="head clear">

		<span class="checkbox pct5">&nbsp;</span>
		<span class="time pct15"><?php _e('Date &amp; Time'); ?></span>
		<span class="user pct15"><?php _e('User'); ?></span>
		<span class="ip pct10"><?php _e('IP'); ?></span>
		<span class="module pct10"><?php _e('Module'); ?></span>
		<span class="type pct10"><?php _e('Type'); ?></span>
		<span class="severity pct10"><?php _e('Severity'); ?></span>
		<span class="message pct25"><?php _e('Message'); ?></span>

	</div>

	<div class="item clear">
		<span class="pct5">&nbsp;</span>
		<span class="pct15"><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'pct90')); ?></span>
		<span class="pct15"><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('address', $addresses, $address, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'pct90')); ?></span>
		<span class="pct25"><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></span>
	</div>
	
	<?php if (isset($years)) { ?>
	<div class="manage logs">

		<?php $theme->display('logs_items'); ?>

	</div>

	<?php } else { ?>

	<div class="item clear">

	<span class="pct5">&nbsp;</span><span class="pct90"><?php _e('There are no logs to be displayed at this time.'); ?></span><span class="pct5">&nbsp;</span>

	</div>
	<?php } ?>

</div>

<div class="container transparent item controls">

	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox_2" name="master_checkbox_2">
		<label class="selectedtext minor none" for="master_checkbox_2"><?php _e('None selected'); ?></label>
	</span>
	<ul class="dropbutton">
		<?php $page_actions = array(
			'delete' => array('action' => 'itemManage.update(\'delete\');return false;', 'title' => _t('Delete Selected'), 'label' => _t('Delete Selected') ),
			'purge' => array('action' => 'itemManage.update(\'purge\');return false;', 'title' => _t('Purge Logs'), 'label' => _t('Purge Logs') ),
		);
		$page_actions = Plugins::filter('logs_manage_actions', $page_actions);
		foreach( $page_actions as $page_action ) : ?>
			<li><a href="*" onclick="<?php echo $page_action['action']; ?>" title="<?php echo $page_action['title']; ?>"><?php echo $page_action['label']; ?></a></li>
		<?php endforeach; ?>
	</ul>

</div>

</form>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxLogDelete;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'logs')) ?>";
itemManage.fetchReplace = $('.logs');

</script>

<?php include('footer.php'); ?>
