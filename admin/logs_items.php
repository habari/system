<?php foreach( $logs as $log ): ?>
<div class="item clear">
		<span class="checkbox pct5"><span><input type="checkbox" name="checkbox_ids[<?php echo $log->id; ?>]" id="checkbox_ids[<?php echo $log->id; ?>]"></span></span>
		<span class="time pct15 minor"><span><?php $log->timestamp->out( "M j, Y" ); ?> &middot; <?php $log->timestamp->out( "H:i" ); ?></span></span>
		<span class="user pct15 minor"><span>
			<?php if ( $log->user_id ) { 
				if ( $user = User::get_by_id( $log->user_id ) ) {
					 echo $user->displayname;
				} else {
					echo $log->user_id;
				}
			} ?>&nbsp;
		</span></span>
		<span class="ip pct10 minor"><span><?php echo long2ip($log->ip); ?></span></span>
		<span class="module pct10 minor"><span><?php echo $log->module; ?></span></span>
		<span class="type pct10 minor"><span><?php echo $log->type; ?></span></span>
		<span class="severity pct5 minor"><span><?php echo $log->severity; ?></span></span>
		<span class="message pct30 minor less"><span><?php echo Utils::truncate($log->message, 40, false); ?></span></span>
		<span class="message pct30 minor more"><span><?php echo $log->message; ?></span></span>
</div>
<?php endforeach; ?>
