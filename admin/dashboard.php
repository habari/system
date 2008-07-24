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
		printf(
			_t( '%1$s has been active for %2$s'),
			Options::get('title'),
			!empty( $active_msg) ? Format::and_list( $active_msg ) : '0 ' . _t( 'days' )
		);
		?><br>

		<?php
		$content_type_msg= array();
		if ( !empty( $stats['page_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'page' ), 'status' => Post::status( 'published' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'page' ), '%d ' . _t( 'pages' ), $stats['page_count'] ), $stats['page_count'] );
			$message.= '</a>';
			$content_type_msg[]= $message;
		}
		if ( !empty( $stats['entry_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ) ) ) . '">';
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
			$status_report = sprintf( _n( '[You] have published %1$s%2$s', 'The [%3$d authors] have published %1$s%2$s', $stats['author_count'] ),
				Format::and_list( $content_type_msg ),
				!empty( $comment_tag_msg ) ? _t( ' with ' ) . Format::and_list( $comment_tag_msg ) : "",
				$stats['author_count'] );

			$status_report = str_replace( array( '[', ']' ),
				array( '<a href="' . URL::get( 'admin', array('page'=>'users') ) . '">', '</a>' ),
				$status_report );

			echo $status_report; 
		}
		?></p>

		<p><?php
		$message_bits= array();
		if ( ! empty( $stats['entry_draft_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'entry draft' ), '%d ' . _t( 'entry drafts' ), $stats['entry_draft_count'] ), $stats['entry_draft_count'] );
			$message.= '</a>';
			$message_bits[]= $message;
		}
		if ( ! empty( $stats['user_entry_scheduled_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'scheduled' ) ) ) . '">';
			$message.= sprintf( _n( '%d ' . _t( 'scheduled post' ), '%d ' . _t( 'scheduled posts' ), $stats['user_entry_scheduled_count'] ), $stats['user_entry_scheduled_count' ] );
			$message.= '</a>';
			$message_bits[]= $message;
		}
		if ( ! empty( $stats['page_draft_count'] ) ) {
			$message= '<a href="' . URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( 'page' ), 'status' => Post::status( 'draft' ) ) ) . '">';
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
			printf( _t('You have %s'), Format::and_list( $message_bits) );
		}
		?></p>

		<?php

			if ( isset( $updates ) && count( $updates ) > 0 ) {

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

					$update_text= sprintf( $update_text, $update['url'], $update['name'], $update['latest_version'] );
					echo "<p class='{$class}'>{$update_text}</p>";

				}

			}

		?>

</div>

<?php if ( $first_run ): ?>
<div class="container dashboard">
	<div class="item">
	<p><em>Welcome to Habari! This is the first time you've been here, so a quick tour is in order.</em></p>
	<p>In the top left corner of the window you'll find a dropdown menu which provides links to all the various sections of the Habari admin. This menu can be accessed with keyboard shortcuts. &ldquo;Q&rdquo; will bring focus to the menu, then press one of the indicated keys to go to another page.</p>
	<p>Wondering what you can do on some of the administrative pages? There are two &ldquo;Create&rdquo; pages for entries and pages. Entries are like journal entries and are filed chronologically. Pages are filed separately and are great for things like telling about the authors on your site. There are also two &ldquo;Manage&rdquo; links where you can find, edit, and delete entries and pages.</p>
	<p>Below the horizontal separator is &ldquo;Dashboard&rdquo;, which takes you back here. &ldquo;Options&rdquo; lets you make changes to the entire blog (title, tagline, that sort of thing). &ldquo;Plugins&rdquo; is where you control, well, plugins. There are a few included, and there are dozens more <a href='http://wiki.habariproject.org/en/Available_Plugins'>plugins</a> available. &ldquo;Themes&rdquo; is where you can change how your blog looks to visitors. More publicly available <a href='http://wiki.habariproject.org/en/Available_Themes'>themes</a> are listed in the wiki. &ldquo;Users&rdquo; is where you control what the registered visitors, authors, and fellow admins can do on the site. Finally &ldquo;Import&rdquo; allows you to bring in your posts from another blogging platform. Just because you're using Habari doesn't mean you have to lose your old work.</p>
	<p>Below this message is your &ldquo;Dashboard&rdquo;. It is rather empty out of the box, but you can add dashboard modules which give you quick information about activity on your blog. The Habari Community recommends heading immediately to the <a href="<?php echo URL::get( 'admin', 'page=plugins' )?>">plugins page</a> and activating the &ldquo;Core Dashboard Modules&rdquo; so that you will see something here.</p>
	<p>If this hasn't covered everything you need to know, there is a link to the <a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href);return false;" title="Habari Manual">manual</a> at the bottom of every page in the admin area. This message will disappear next time you visit.</p>
	</div>
</div>
	
<?php endif; ?>
<div class="container dashboard transparent">

	<?php $theme->display('dashboard_modules'); ?>

</div>


<?php include( 'footer.php' ); ?>
