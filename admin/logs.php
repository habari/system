<?php
include_once( 'header.php' );
?>
<div class="container">
<hr>
	<?php if(Session::has_messages()) {Session::messages_out();} ?>
	<div class="column prepend-1 span-22 append-1">
		<p>Take a look behind the curtain and see the Great Oz at work.  Here you will see an up-to-date log of Habari's activity.</p>
	<form method="post" action="<?php URL::out('admin', 'page=logs'); ?>" class="buttonform">
	<p>Search log entries:
	<input type="textbox" size="50" name='search' value="<?php echo $search; ?>"> <input type="submit" name="do_search" value="<?php _e('Search'); ?>">
	<?php printf( _t('Limit: %s'), Utils::html_select('limit', $limits, $limit)); ?>
	<?php printf( _t('Page: %s'), Utils::html_select('index', $pages, $index)); ?>
	<a href="<?php URL::out('admin', 'page=logs'); ?>">Reset</a>
	</p>
		<table id="log-activity-table" width="100%" cellspacing="0">
			<thead>
				<tr>
					<th class="span-1"></th>
					<th align="left">Date</th>
					<th align="left">User</th>
					<th align="left">Module</th>
					<th align="left">Type</th>
					<th align="center">Severity</th>
					<th align="left">Message</th>
				</tr>
			</thead>
			<tr>
			<td class="span-1"></td>
			<td><?php echo Utils::html_select('date', $dates, $date, array( 'class'=>'longselect')); ?></td>
			<td><?php echo Utils::html_select('user', $users, $user, array( 'class'=>'longselect')); ?></td>
			<td><?php echo Utils::html_select('module', $modules, $module, array( 'class'=>'longselect')); ?></td>
			<td><?php echo Utils::html_select('type', $types, $type, array( 'class'=>'longselect')); ?></td>
			<td><?php echo Utils::html_select('severity', $severities, $severity, array( 'class'=>'longselect')); ?></td>
			<td align="right"><input type="submit" name="filter" value="<?php _e('Filter'); ?>"></td>
			</tr>
			<?php foreach( $logs as $log ){ ?>
			<tr>
				<td align="left"><input type="checkbox" name="log_ids[]" value="<?php echo $log->id; ?>"></td>
				<td><?php echo $log->timestamp; ?></td>
				<td><?php if ( $log->user_id ) { $user= User::get_by_id( $log->user_id ); echo $user->username; } ?></td>
				<td><?php echo $log->module; ?></td>
				<td><?php echo $log->type; ?></td>
				<td><?php echo $log->severity; ?></td>
				<td><p><?php echo $log->message; ?></p></td>
			</tr>
			<?php } ?>
			<tr><td colspan="7">
			<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
			 <input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
			 <input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
			 <input type="submit" name="do_delete" value="<?php _e('Delete'); ?>">
		</table>
	</div>
</div>
	<?php include('footer.php');?>
