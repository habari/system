<?php include( 'header.php' ); ?>

<div class="container dashboardinfo transparent">
		<p>
		<?php
		$active_msg= array();
		if ( !empty( $active_time['years'] ) ) {
			$message= sprintf( _n( '%1$d ' . _t( 'year' ), '%1$d ' . _t( 'years' ), $active_time['years'] ), $active_time['years'] );
			$active_msg[]= $message;
		}
		if ( !empty( $active_time['months'] ) ) {
			$message= sprintf( _n( '%1$d ' . _t( 'month' ), '%1$d ' . _t( 'months' ), $active_time['months'] ), $active_time['months'] );
			$active_msg[]= $message;
		}
		if ( !empty( $active_time['days'] ) ) {
			$message= sprintf( _n( '%1$d ' . _t( 'day' ), '%1$d ' . _t( 'days' ), $active_time['days'] ), $active_time['days'] );
			$active_msg[]= $message;
		}
		if ( !empty( $active_msg ) ) {
			echo Options::get('title') . _t( ' has been active for ' ) .  Format::and_list( $active_msg );
		}
		?><br>

		<?php
		$content_type_msg= array();
		if ( !empty( $stats['page_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'page' ), 'status' => Post::status( 'published' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'page' ), '%d ' . _t( 'pages' ), $stats['page_count'] ), $stats['page_count'] );
			$message.= '</a>';
			$content_type_msg[]= $message;
		}
		if ( !empty( $stats['entry_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'entry' ), '%d ' . _t( 'entries' ), $stats['entry_count'] ), $stats['entry_count'] );
			$message.= '</a>';
			$content_type_msg[]= $message;
		}

		$comment_tag_msg= array();
		if ( !empty( $stats['comment_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_APPROVED ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'comment' ), '%d ' . _t( 'comments' ), $stats['comment_count'] ), $stats['comment_count'] );
			$message.= '</a>';
			$comment_tag_msg[]= $message;
		}
		if ( !empty( $stats['tag_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'tags' ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'tag' ), '%d ' . _t( 'tags' ), $stats['tag_count'] ), $stats['tag_count'] );
			$message.= '</a>';
			$comment_tag_msg[]= $message;
		}

		if ( !empty( $content_type_msg ) ) {
			printf(
				_n( '<a href="%2$s">' . _t( 'You' ) . '</a>', _t( 'The' ) . ' <a href="%2$s">%1$d ' . _t( 'authors' ) . '</a>', $stats['author_count'] ) .
				_t( ' have published ' ) .
				Format::and_list( $content_type_msg ) . '%3$s',
				$stats['author_count'],
				URL::get( 'admin', array('page' => 'users' ) ),
				!empty( $comment_tag_msg ) ? _t( ' with ' ) .  Format::and_list( $comment_tag_msg ) : ''
			);
		}
		?></p>

		<p><?php
		$message_bits= array();
		if ( ! empty( $stats['entry_draft_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'entry draft' ), '%d ' . _t( 'entry drafts' ), $stats['entry_draft_count'] ), $stats['entry_draft_count'] );
			$message.= '</a>';
			$message_bits[]= $message;
		}
		if ( ! empty( $stats['user_entry_scheduled_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'scheduled' ) ) ) . '</a>';
			$message.= sprintf( _n( '%d ' . _t( 'scheduled entry' ), '%d ' . _t( 'scheduled entries' ), $stats['user_entry_scheduled_count'] ), $stats['user_entry_scheduled_count' ] );
			$message.= '</a>';
			$message_bits[]= $message;
		}
		if ( ! empty( $stats['page_draft_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'page' ), 'status' => Post::status( 'draft' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'page draft' ), '%d ' . _t( 'page drafts' ), $stats['page_draft_count'] ), $stats['page_draft_count'] );
			$message.= '</a>';
			$message_bits[]= $message;
		}
		if ( ! empty(  $stats['unapproved_comment_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'comments', 'status' => Comment::STATUS_UNAPPROVED ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'comment awaiting approval' ), '%d ' . _t( 'comments awaiting approval' ), $stats['unapproved_comment_count'] ), $stats['unapproved_comment_count'] );
			$message.= '</a>';
			$message_bits[]= $message;
		}

		if ( !empty( $message_bits ) ) {
			echo _t('You have ' ) . Format::and_list( $message_bits);
		}
		?></p>

		<?php

			if ( count( $updates ) > 0 ) {

				foreach ( $updates as $update ) {

					$class= implode( ' ', $update['severity'] );

					if ( in_array( 'critical', $update['severity'] ) ) {
						$update_text= _t( '<a href="%1s">%2s %3s</a> is a critical update.' );
					}
					elseif ( count( $update['severity'] ) > 1 ) {
						$update_text= _t( '<a href="%1s">%2s %3s</a> contains bug fixes and additional features.' );
					}
					elseif ( in_array( 'bugfix', $update['severity'] ) ) {
						$update_text= _t( '<a href="%1s">%2s %3s</a> contains bug fixes.' );
					}
					elseif ( in_array( 'feature', $update['severity'] ) ) {
						$update_text= _t( '<a href="%1s">%2s %3s</a> contains additional features.' );
					}

					$update_text= sprintf( $updatetext, $update['url'], $update['name'], $update['latest_version'] );
					echo "<p class='{$class}'>{$update_text}</p>";

				}

			}

		?>

</div>

<div class="container dashboard transparent">

	<ul class="modules">
		<?php foreach($modules as $modulename => $module): ?>
		<li class="module <?php echo $modulename; ?>module" id="<?php echo $modulename; ?>module">
			<?php echo $module; ?>
		</li>
		<?php endforeach; ?>
	</ul>

</div>


<?php include( 'footer.php' ); ?>
