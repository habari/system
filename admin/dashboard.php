<?php include( 'header.php' ); ?>

<div class="container dashboardinfo transparent">
		<!--[if lte IE 6]>
		<p><?php
_e( 'Oh, great!	You\'re using IE6!  I\'ve finally found someone I can pawn this old betamax player off on!<br />
If you\'re reading this, you\'re surfing using Internet Explorer 6, an eight-year-old browser that cannot cope with the demands of the modern internet.
<a href="http://www.apple.com/safari/download/">Safari</a>, <a href="http://www.google.com/chrome">Google Chrome</a>, or a more recent version of 
<a href="http://www.microsoft.com/windows/Internet-explorer/default.aspx">Internet Explorer</a>.' ); ?>
		</p>
		<![endif]-->

		<p>
		<?php
		$active_msg = array();
		if ( !empty( $active_time['years'] ) ) {
			$message = sprintf( _n( '%d year', '%d years', $active_time['years'] ), $active_time['years'] );
			$active_msg[] = $message;
		}
		if ( !empty( $active_time['months'] ) ) {
			$message = sprintf( _n( '%d month', '%d months', $active_time['months'] ), $active_time['months'] );
			$active_msg[] = $message;
		}
		if ( !empty( $active_time['days'] ) ) {
			$message = sprintf( _n( '%d day', '%d days', $active_time['days'] ), $active_time['days'] );
			$active_msg[] = $message;
		}
		printf(
			_t( '%1$s has been active for %2$s'),
			Options::get('title'),
			!empty( $active_msg) ? Format::and_list( $active_msg ) : '0 ' . _t( 'days' )
		);
		?><br>

		<?php
		$content_type_msg = array();
		$user = User::identify();
		if ( !empty( $stats['page_count'] ) ) {
			$message = sprintf( _n( '%d page', '%d pages', $stats['page_count'] ), $stats['page_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'post_page' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'page' ), 'status' => Post::status( 'published' ) ) ) ) . '">' . $message . '</a>';
			}
			$content_type_msg[] = $message;
		}
		if ( !empty( $stats['entry_count'] ) ) {
			$message = sprintf( _n( '%d entry', '%d entries', $stats['entry_count'] ), $stats['entry_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'post_entry' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ) ) ) ) . '">' . $message . '</a>';
			}
			$content_type_msg[] = $message;
		}

		$comment_tag_msg = array();
		if ( !empty( $stats['comment_count'] ) ) {
			$message = sprintf( _n( '%d comment', '%d comments', $stats['comment_count'] ), $stats['comment_count'] );
			$perms = array( 'manage_all_comments' => TRUE, 'manage_own_post_comments' => TRUE );
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_APPROVED ) ) ) . '">' . $message . '</a>';
			}
			$comment_tag_msg[] = $message;
		}
		if ( !empty( $stats['tag_count'] ) ) {
			$message = sprintf( _n( '%d tag', '%d tags', $stats['tag_count'] ), $stats['tag_count'] );
			$perms = array( 'manage_tags' => TRUE );
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'tags' ) ) ) . '">' . $message . '</a>';
			}
			$comment_tag_msg[] = $message;
		}
		if ( !empty( $content_type_msg ) ) {
			$status_report = sprintf( _n( '[You] have published %1$s%2$s', 'The [%3$d authors] have published %1$s%2$s', $stats['author_count'] ),
				Format::and_list( $content_type_msg ),
				!empty( $comment_tag_msg ) ? _t( ' with ' ) . Format::and_list( $comment_tag_msg ) : "",
				$stats['author_count'] );

			$perms = array( 'manage_users' => TRUE );
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
		if ( !empty( $stats['entry_draft_count'] ) ) {
			$message = sprintf( _n( '%d entry draft', '%d entry drafts', $stats['entry_draft_count'] ), $stats['entry_draft_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'post_entry' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ), 'user_id' => $user->id ) ) ) . '">' . $message . '</a>';
			}
			$message_bits[] = $message;
		}
		if ( !empty( $stats['user_entry_scheduled_count'] ) ) {
			$message = sprintf( _n( '%d scheduled post' , '%d scheduled posts' , $stats['user_entry_scheduled_count'] ), $stats['user_entry_scheduled_count' ] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'post_entry' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'status' => Post::status( 'scheduled' ) ) ) ) . '">' . $message . '</a>';
			}
			$message_bits[] = $message;
		}
		if ( !empty( $stats['page_draft_count'] ) ) {
			$message = sprintf( _n( '%d page draft', '%d page drafts', $stats['page_draft_count'] ), $stats['page_draft_count'] );
			$perms = array(
				'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				'post_page' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
			);
			if ( $user->can_any( $perms ) ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'page' ), 'status' => Post::status( 'draft' ) ) ) ) . '">' . $message . '</a>';
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

			if ( !empty(  $stats['spam_comment_count'] ) && User::identify()->info->dashboard_hide_spam_count != true ) {
				$message = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_SPAM ) ) ) . '">';
				$message .= sprintf( _n( '%d spam comment awaiting moderation', '%d spam comments awaiting moderation', $stats['spam_comment_count'] ), $stats['spam_comment_count'] );
				$message .= '</a>';
				$message_bits[] = $message;
			}
		}
		if ( !empty( $message_bits ) ) {
			_e('You have %s', array(Format::and_list( $message_bits)) );
		}
		?></p>

		<?php

			if ( isset( $updates ) && count( $updates ) > 0 ) {

				foreach ( $updates as $update ) {

					$class = implode( ' ', $update['severity'] );

					if ( in_array( 'critical', $update['severity'] ) ) {
						$update_text = _t( '<a href="%1s">%2s %3s</a> is a critical update.' );
					}
					elseif ( count( $update['severity'] ) > 1 ) {
						$update_text = _t( '<a href="%1s">%2s %3s</a> contains bug fixes and additional features.' );
					}
					elseif ( in_array( 'bugfix', $update['severity'] ) ) {
						$update_text = _t( '<a href="%1s">%2s %3s</a> contains bug fixes.' );
					}
					elseif ( in_array( 'feature', $update['severity'] ) ) {
						$update_text = _t( '<a href="%1s">%2s %3s</a> contains additional features.' );
					}
					else {
						$update_text = _t( '<a href="%1s">%2s %3s</a> is a new release.' );
					}

					$update_text = sprintf( $update_text, $update['url'], $update['name'], $update['latest_version'] );
					echo "<p class='{$class}'>{$update_text}</p>";

				}

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

<?php if ( count( $modules ) > 0 ): ?>

<div class="container dashboard transparent">

	<?php $theme->display('dashboard_modules'); ?>

</div>

<?php endif; ?>

<?php include( 'footer.php' ); ?>
