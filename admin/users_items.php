<!-- a b c d e f g h i j k l m n o p q r s t u v x y z æ å ø -->

<ul>
	<?php foreach (Users::get_all() as $user) {
		if ( $user->username == $currentuser->username )
			$url = Url::get( 'admin', 'page=user' );
		else
			$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
	?>

	<li class="item clear">
		<span class="checkbox pct5"><span><input type="checkbox" name="checkbox_ids[<?php echo $user->id; ?>]" id="checkbox_ids[<?php echo $user->id; ?>]"></span></span>
		<span class="user pct95"><a href="<?php echo $url ?>" title="<?php _e('Open '. $user->displayname .'\'s user page') ?>"><?php echo $user->displayname ?></a></span><br>

		<span class="aka pct95">
		<?php
			if ( !$user->info->authenticate_time ) {
				_e( "was not logged in yet.");
			}
			else {
				$message_bits = array();
				$post_statuses= Post::list_post_statuses();
				unset( $post_statuses[array_search( 'any', $post_statuses )] );
				foreach ( $post_statuses as $status_name => $status_id ) {
					$count= Posts::count_by_author( $user->id, $status_id );
					if ( $count > 0 ) {
						$message = '<strong><a href="' . URL::get( 'admin', array( 'page' => 'posts', 'user_id' => $user->id, 'type' => Post::type( 'any' ), 'status' => $status_id ) ) . '">';
						$message.= sprintf( '%d ' . _n( _t( $status_name . ' post' ), _t( $status_name . ' posts' ), $count ), Posts::count_by_author( $user->id, $status_id ) ) ;
						$message.= '</a></strong>';
						$message_bits[]= $message;
					}
				}

				printf( _t( 'was last seen %1$s at %2$s and currently has %3$s'),
					"<strong>" . date('M j, Y', strtotime($user->info->authenticate_time)) . "</strong>",
					"<strong>" . date('H:i', strtotime($user->info->authenticate_time)) . "</strong>",
					Format::and_list( $message_bits )
				);
			}
		?>
		</span>
	</li>

	<?php } ?>
</ul>
