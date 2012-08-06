<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include( 'header.php' ); ?>

<div class="container dashboardinfo transparent">
		<!--[if lte IE 6]>
		<p><?php
		
			$ie6_age = HabariDateTime::difference( 'now', 'August 27, 2001' );
			
			echo _t( "Oh, great! You're using IE6! I've finally found someone I can pawn this old betamax player off on!" ) . '<br />';
			echo _t( "If you're reading this you're surfing using Internet Explorer 6, a browser that is %d %s old and cannot cope with the demands of the modern internet.", array( $ie6_age['y'], _n( 'year', 'years', $ie6_age['y'] ) ) ) . '<br />';
			echo _t( 'Consider switching to <a href="http://mozilla.com">Mozilla Firefox</a>, <a href="http://www.apple.com/safari/download/">Safari</a>, <a href="http://www.google.com/chrome">Google Chrome</a>, or a more recent version of <a href="http://www.microsoft.com/windows/Internet-explorer/default.aspx">Internet Explorer</a>.' );
			
		?></p>
		<![endif]-->

		<p>
		<?php
		if ( isset($active_time) ) {
			_e( '%s has been active for %s', array( Options::get('title'), $active_time->friendly( 3, false ) ) );
		}
		?>
		<br>

		<?php
		$content_type_msg = array();
		$user = User::identify();
		if ( !empty( $stats['post_count'] ) ) {
			$message = sprintf( _n( '%d post', '%d posts', $stats['post_count'] ), $stats['post_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'any' ), 'status' => Post::status( 'published' ) ) ) ) . '">' . $message . '</a>';
			}
			$content_type_msg[] = $message;
		}

		$comment_tag_msg = array();
		if ( !empty( $stats['comment_count'] ) ) {
			$message = sprintf( _n( '%d comment', '%d comments', $stats['comment_count'] ), $stats['comment_count'] );
			$perms = array( 'manage_all_comments' => true, 'manage_own_post_comments' => true );
			if ( $user->can( 'manage_all_comments' ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_APPROVED ) ) ) . '">' . $message . '</a>';
			}
			$comment_tag_msg[] = $message;
		}

		if ( !empty( $stats['tag_count'] ) ) {
			$message = sprintf( _n( '%d tag', '%d tags', $stats['tag_count'] ), $stats['tag_count'] );
			if ( $user->can( 'manage_tags' ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'tags' ) ) ) . '">' . $message . '</a>';
			}
			$comment_tag_msg[] = $message;
		}
		if ( !empty( $content_type_msg ) ) {
			$status_report = sprintf( _n( '[You] have published %1$s%2$s', 'The [%3$d authors] have published %1$s%2$s', $stats['author_count'] ),
				Format::and_list( $content_type_msg ),
				!empty( $comment_tag_msg ) ? _t( ' with ' ) . Format::and_list( $comment_tag_msg ) : "",
				$stats['author_count'] );

			$perms = array( 'manage_users' => true );
			if ( $user->can_any( $perms ) ) {
				$status_report = str_replace( array( '[', ']' ),
					array( '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array('page'=>'users') ) ) . '">', '</a>' ),
					$status_report );
			}
			else {
				$status_report = str_replace( array( '[', ']' ), array( '', '' ), $status_report );
			}
			echo $status_report;
		}
		?></p>

		<p><?php
		$message_bits = array();
		$user= User::identify();
		if ( !empty( $stats['user_draft_count'] ) ) {
			$message = sprintf( _n( '%d draft', '%d drafts', $stats['user_draft_count'] ), $stats['user_draft_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'any' ), 'status' => Post::status( 'draft' ), 'user_id' => $user->id ) ) ) . '">' . $message . '</a>';
			}
			$message_bits[] = $message;
		}
		if ( !empty( $stats['user_scheduled_count'] ) ) {
			$message = sprintf( _n( '%d scheduled post' , '%d scheduled posts' , $stats['user_scheduled_count'] ), $stats['user_scheduled_count' ] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'status' => Post::status( 'scheduled' ) ) ) ) . '">' . $message . '</a>';
			}
			$message_bits[] = $message;
		}
		if ( $user->can_any( array( 'manage_all_comments' => true, 'manage_own_post_comments' => true ) ) ) {
			if ( !empty(  $stats['unapproved_comment_count'] ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_UNAPPROVED ) ) ) . '">';
				$message .= sprintf( _n( '%d comment awaiting approval', '%d comments awaiting approval', $stats['unapproved_comment_count'] ), $stats['unapproved_comment_count'] );
				$message .= '</a>';
				$message_bits[] = $message;
			}

			if ( !empty(  $stats['spam_comment_count'] ) && $user->info->dashboard_hide_spam_count != true ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_SPAM ) ) ) . '">';
				$message .= sprintf( _n( '%d spam comment', '%d spam comments', $stats['spam_comment_count'] ), $stats['spam_comment_count'] );
				$message .= '</a>';
				$message_bits[] = $message;
			}
		}
		if ( !empty( $message_bits ) ) {
			_e('You have %s', array(Format::and_list( $message_bits)) );
		}
		?></p>

		<?php
			
			if ( !empty( $updates ) ) {
				
				?>
				
					<ul class="updates">
					
						<?php

							foreach ( $updates as $beacon_id => $beacon ) {
																
								$u_strings = array();
								foreach ( $beacon['updates'] as $u_version => $u ) {
									
									if ( !empty( $u['date'] ) ) {
										$u_title = _t( '%1$s update released on %2$s: %3$s', array( MultiByte::ucfirst( $u['severity'] ), HabariDateTime::date_create( $u['date'] )->format( 'Y-m-d' ), Utils::htmlspecialchars( $u['text'] ) ) );
									}
									else {
										$u_title = _t( '%1$s update: %3$s', array( MultiByte::ucfirst( $u['severity'] ), $u['date'], Utils::htmlspecialchars( $u['text'] ) ) );
									}
									
									if ( !empty( $u['url'] ) ) {
										$u_string = sprintf( '<a href="%1$s" title="%2$s" class="%3$s">%4$s</a>', $u['url'], $u_title, $u['severity'], $u['version'] );
									}
									else {
										$u_string = sprintf( '<span title="%1$s" class="%2$s">%3$s</span>', $u_title, $u['severity'], $u['version'] );
									}
									
									// add it to the array of updates available for this plugin
									$u_strings[ $u['version'] ] = $u_string;
									
								}
								
								$u_strings = Format::and_list( $u_strings );
								
								?>
								
									<li class="update">
										<?php echo _t( '%1$s <a href="%2$s">%3$s</a> has the following updates available: %4$s', array( MultiByte::ucfirst( $beacon['type'] ), $beacon['url'], $beacon['name'], $u_strings ) ); ?>
									</li>
								
								<?php
			
							}
							
						?>
						
					</ul>
					
				<?php
				
			}

		?>

</div>

<?php if ( $first_run ):
	$msg = _t('Welcome to Habari! We hope that you will jump right in and start exploring. If you get stuck or want to learn more about some of the advanced features, we encourage you to read the [manual], which is bundled with every Habari install. This link also appears at the bottom of every page in the admin area.');
 	$msg = str_replace( array( '[', ']' ), array( '<a href="' . Site::get_url('habari') . '/doc/manual/index.html" onclick="popUp(this.href);return false;" title="' . _t('Habari Manual') . '">', '</a>' ), $msg );
?>

<div class="container dashboard transparent">
	<div class="item">
	<p><?php echo $msg; ?></p>
	<p><?php _e( 'This message will disappear next time you visit.' ); ?></p>
	</div>
</div>

<?php endif; ?>

<div class="container dashboard transparent">

	<ul class="modules">
		<?php echo $theme->display('dashboard_modules'); ?>
	</ul>

</div>

<?php include( 'footer.php' ); ?>
