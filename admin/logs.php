<?php include_once( 'header.php' ); ?>


<form method="post" action="<?php URL::out('admin', array( 'page' => 'logs' ) ); ?>" class="buttonform">


<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('0-0 of 0'); ?></span>
	<span class="search pct50"><input type="search" name='search' placeholder="<?php _e('Type and wait to search any log entry component'); ?>" autosave="habaricontent" results="10" value="<?php echo $search; ?>"></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>

	<?php if(isset($years)) { ?><div class="timeline">
		<div class="years">
			<?php $theme->display( 'timeline_items' )?>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div><?php } ?>

</div>

<div class="container">

	<?php if(isset($years)) { ?><div class="item clear">
		<div class="head clear">

			<span class="checkbox pct5">&nbsp;</span>
			<span class="time pct15"><?php _e('Date &amp; Time'); ?></span>
			<span class="user pct15"><?php _e('User'); ?></span>
			<span class="ip pct10"><?php _e('IP'); ?></span>
			<span class="module pct10"><?php _e('Module'); ?></span>
			<span class="type pct10"><?php _e('Type'); ?></span>
			<span class="severity pct5"><?php _e('Severity'); ?></span>
			<span class="message pct30"><?php _e('Message'); ?></span>

		</div>
	</div>


	<div class="item clear">
		<span class="pct5">&nbsp;</span>
		<span class="pct15"><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'pct90')); ?></span>
		<span class="pct15"><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('address', $addresses, $address, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'pct90')); ?></span>
		<span class="pct10"><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'pct90')); ?></span>
		<span class="pct5"><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'pct90')); ?></span>
		<td align="right"><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></span>
	</div>
	
	<div class="manage logs">

	<?php $theme->display('logs_items'); ?>

	</div><?php } else { ?><div class="item clear">
		<span class="pct5">&nbsp;</span><span class="pct90"><?php _e('There are no logs to be displayed at this time.'); ?></span><span class="pct5">&nbsp;</span>
	</div><?php } ?>
	

</div>


<?php if(isset($years)) { ?><div class="container transparent">

	<div class="item controls">
		<span class="pct25">
			<input type="checkbox">
			<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
		</span>
		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
		
		<input type="button" value="<?php _e('Delete'); ?>" class="button delete">
	</div>

</div><?php } ?>


</form>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxLogDelete;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'logs')) ?>";
itemManage.fetchReplace = $('.logs');
itemManage.inEdit = false;

</script>

<?php include('footer.php'); ?>
