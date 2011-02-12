<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<ul>
	<?php foreach (Users::get_all() as $user) {
		if ( $user->username == $currentuser->username ) {
			$url = Url::get( 'admin', 'page=user' );
		}
		else {
			$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
		}
	?>

	<li class="item clear">
		<div class="clear">
			<span class="checkbox pct5"><span><input type="checkbox" class="checkbox" name="checkbox_ids[<?php echo $user->id; ?>]" id="checkbox_ids[<?php echo $user->id; ?>]"></span></span>
			<span class="user pct95"><a href="<?php echo $url ?>" title="<?php printf( _t('Open %s\'s user page'), $user->displayname ) ?>"><?php echo $user->displayname ?></a></span>
		</div>

		<div class="clear">
			<span class="nothing pct5">&nbsp;</span>
			<span class="aka pct90">

			<?php

				if ( !$user->info->authenticate_time ) {
					$last_login_message = _t( 'has not logged in yet' );
				}
				else {

					$last_login_message = _t( 'was last seen %1$s at %2$s' );
					$last_login_message = sprintf( $last_login_message,
						'<strong>' . date('M j, Y', strtotime( $user->info->authenticate_time ) ) . '</strong>',
						'<strong>' . date('H:i', strtotime( $user->info->authenticate_time ) ) . '</strong>'
					);

				}

				$message_bits = array();

				$post_statuses = Post::list_post_statuses();
				unset( $post_statuses[ array_search( 'any', $post_statuses ) ] );

				foreach( $post_statuses as $status_name => $status_id ) {

					$count = Posts::count_by_author( $user->id, $status_id );

					if ( $count > 0 ) {
						$message = '<strong><a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'user_id' => $user->id, 'type' => Post::type( 'any' ), 'status' => $status_id ) ) ) . '">';
						$message .= sprintf( '%d ' . _n( _t( $status_name . ' post' ), _t( $status_name . ' posts' ), $count ), $count ) ;
						$message .= '</a></strong>';
						$message_bits[] = $message;
					}

				}

				if ( !empty( $message_bits ) ) {
					$string = sprintf( _t( '%1$s and currently has %2$s' ), $last_login_message, Format::and_list( $message_bits ) );
				}
				else {
					$string = $last_login_message;
				}

				echo $string;

			?>

			</span>
		</div>
	</li>

	<?php } ?>
</ul>
