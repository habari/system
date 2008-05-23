<?php
include_once( 'header.php' );
?>
<div class="container">
<hr>
	<div class="column span-20">
		<p><?php _e('Take a look behind the curtain and see the Great Oz at work. Here you will see an up-to-date log of Habari\'s activity.'); ?></p>
	<form method="post" action="<?php URL::out('admin', 'page=logs'); ?>" class="buttonform">
	<p><?php _e('Search log entries:'); ?>
	<input type="textbox" size="50" name='search' value="<?php echo $search; ?>"> <input type="submit" name="do_search" value="<?php _e('Search'); ?>">
	<?php printf( _t('Limit: %s'), Utils::html_select('limit', $limits, $limit, array( 'class'=>'pct10'))); ?>
	<?php printf( _t('Page: %s'), Utils::html_select('index', $pages, $index, array( 'class'=>'pct10'))); ?>
	<a href="<?php URL::out('admin', 'page=logs'); ?>"><?php _e('Reset'); ?></a>
	</p>
		<table id="log-activity-table" width="100%" cellspacing="0">
			<thead>
				<tr>
					<th class="span-1"></th>
					<th align="left"><?php _e('Date'); ?></th>
					<th align="left"><?php _e('User'); ?></th>
					<th align="left"><?php _e('Module'); ?></th>
					<th align="left"><?php _e('Type'); ?></th>
					<th align="center"><?php _e('Severity'); ?></th>
					<th align="center"><?php _e('Address'); ?></th>
					<th align="left"><?php _e('Message'); ?></th>
				</tr>
			</thead>
			<tr>
			<td class="span-1"></td>
			<td><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'pct100')); ?></td>
			<td><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'pct100')); ?></td>
			<td><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'pct100')); ?></td>
			<td><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'pct100')); ?></td>
			<td><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'pct100')); ?></td>
			<td><?php echo Utils::html_select('address', $addresses, $address, array( 'class'=>'pct100')); ?></td>
			<td align="right"><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></td>
			</tr>
			<?php foreach( $logs as $log ){ ?>
			<tr>
				<td align="left"><input type="checkbox" name="log_ids[]" value="<?php echo $log->id; ?>"></td>
				<td><?php echo $log->timestamp; ?></td>
				<td><?php if ( $log->user_id ) { 
					if ( $user= User::get_by_id( $log->user_id ) ) {
						 echo $user->displayname;
					} else {
						echo $log->user_id;
					}
				} ?></td>
				<td><?php echo $log->module; ?></td>
				<td><?php echo $log->type; ?></td>
				<td><?php echo $log->severity; ?></td>
				<td><?php echo long2ip($log->ip); ?></td>
				<td><p><?php echo $log->message; ?></p></td>
			</tr>
			<?php } ?>
			<tr><td colspan="8">
			<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
			 <input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
			 <input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
			 <input type="submit" name="do_delete" value="<?php _e('Delete'); ?>">
		</table>
		</form>
	</div>
</div>
	<?php include('footer.php');?>
