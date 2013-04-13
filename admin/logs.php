<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include_once( 'header.php' ); ?>

<?php include( 'navigator.php' ); ?>

<?php echo $form; ?>

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

<?php echo $form->dupe(); ?>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxLogDelete;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'logs')) ?>";
itemManage.fetchReplace = $('.logs');

</script>

<?php include('footer.php'); ?>
