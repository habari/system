<?php 
include_once( 'header.php' );
?>
<div class="container">
<hr />
	<div class="dashboard-block c3" id="welcome">
		<p>Take a look behind the curtain and see the Great Oz at work.  Here you will see an up-to-date log of Habari's activity.</p>
	</div>
	<div class="dashboard-block c3" id="log-activity">
		<table id="log-activity" width="100%" cellspacing="0">
			<thead>
				<tr>
					<th align="left">Date</th>
					<th align="left">User</th>
					<th align="left">Type</th>
					<th align="left">Message</th>
					<th align="center">Severity</th>
				</tr>
			</thead>
			<?php foreach( logentry::get() as $log ){ ?>
			<tr>
				<td><?php echo $log->timestamp; ?></td>
				<td><?php if ( $log->user_id ) { $user= User::get_by_id( $log->user_id ); echo $user->username; } ?></td>
				<td><?php echo logentry::get_event_type( $log->type_id ); ?></td>
				<td><p><?php echo $log->message; ?></p></td>
				<td><?php echo LogEntry::severity_name( $log->severity_id ); ?></td>
			</tr>
			<?php } ?>
		</table>
	</div>
</div>
	<?php include('footer.php');?>
