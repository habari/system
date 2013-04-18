<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php foreach ( $logs as $log ): ?>
	<tr>
		<td class="checkbox"><input type="checkbox" class="checkbox log_entry" value="<?php echo $log->id; ?>">
		<td class="time"><?php $log->timestamp->out( "Y-m-d H:i:s" ); ?>
		<td class="user">
			<?php if ( $log->user_id ) {
				if ( $user = User::get_by_id( $log->user_id ) ) {
					echo $user->displayname;
				}
				else {
					echo $log->user_id;
				}
			} ?>
		</td>
		<td class="ip"><?php echo $log->ip; ?></td>
		<td class="module"><?php echo $log->module; ?></td>
		<td class="type"><?php echo $log->type; ?></td>
		<td class="severity"><?php echo $log->severity; ?></td>
		<td class="message more">
			<?php echo Utils::htmlspecialchars($log->message); ?>
		</td>
	</tr>
<?php endforeach; ?>
