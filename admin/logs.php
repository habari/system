<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include_once( 'header.php' ); ?>

<div class="container transparent item controls">

<?php include( 'navigator.php' ); ?>

<?php echo $form; ?>

</div>

<table class="container main" id="log_data">

	<thead>
		<tr>
			<th class="checkbox">&nbsp;</th>
			<th class="time"><?php _e('Date &amp; Time'); ?></th>
			<th class="user"><?php _e('User'); ?></th>
			<th class="ip"><?php _e('IP'); ?></th>
			<th class="module"><?php _e('Module'); ?></th>
			<th class="type"><?php _e('Type'); ?></th>
			<th class="severity"><?php _e('Severity'); ?></th>
			<th class="message"><?php _e('Message'); ?></th>
		</tr>
		<!-- tr>
			<th>&nbsp;</th>
			<th><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'')); ?></th>
			<th><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'')); ?></th>
			<th><?php echo Utils::html_select('address', $addresses, $address, array( 'class'=>'')); ?></th>
			<th><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'')); ?></th>
			<th><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'')); ?></th>
			<th><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'')); ?></th>
			<th><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></th>
		</tr -->
	</thead>

	<?php if (isset($years)) { ?>
	<tbody class="manage logs">

		<?php $theme->display('logs_items'); ?>

	</tbody>

	<?php } else { ?>

	<tbody><tr><td colspan="9">

		<?php _e('There are no logs to be displayed at this time.'); ?>

	</td></tr></tbody>
	<?php } ?>

</table>

<div class="container transparent item controls">

<?php echo $form->dupe(); ?>
</div>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxLogDelete;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'logs')) ?>";
itemManage.fetchReplace = $('.logs');

</script>

<?php include('footer.php'); ?>
