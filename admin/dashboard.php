<?php include( 'header.php' ); ?>

<div class="container dashboardinfo transparent">
		<p>
		<?php
		printf(
			Options::get('title') .
			_n(' has been active for %1$d year, ', ' has been active for %1$d years, ', $active_time['years']) .
			_n('%2$d month ', '%2$d months ', $active_time['months']) .
			_n('and %3$d day.', 'and %3$d days.', $active_time['days']),
			$active_time['years'], $active_time['months'], $active_time['days']
		 );
		?>
		<br>
		<?php
		printf(
			_n('The <a href="%6$s">%1$d author </a> has ', 'The <a href="%6$s">%1$d authors </a> have ', $stats['author_count']) .
			_n('published <a href="%7$s">%2$d page</a> ', 'published <a href="%7$s">%2$d pages</a> ', $stats['page_count']) .
			_n('and <a href="%8$s">%3$d entry</a> ', 'and <a href="%8$s">%3$d entries</a> ', $stats['entry_count']) .
			_n('with <a href="%9$s">%4$d comment</a> ', 'with <a href="%9$s">%4$d comments</a> ', $stats['comment_count']) .
			_n('and <a href="%10$s">%5$d tag</a>', 'and <a href="%10$s">%5$d tags</a>', $stats['tag_count']),
			$stats['author_count'],
			$stats['page_count'],
			$stats['entry_count'],
			$stats['comment_count'],
			$stats['tag_count'],
			URL::get( 'admin', array('page' => 'users' ) ),
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'page' ), 'status' => Post::status( 'published' ) ) ),
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ) ) ),
			URL::get( 'admin', array( 'page' => 'comments', 'search_status' => Comment::STATUS_APPROVED ) ),
			URL::get( 'admin', array( 'page' => 'tags' ) )
		);
		?></p>
		<p><?php
		printf(
			_n('You currently have <a href="%5$s">%1$d entry draft</a>, ', 'You currently have <a href="%5$s">%1$d entry drafts</a>, ', $stats['entry_draft_count']) .
			_n('<a href="%6$s">%2$d scheduled entry</a>, ', '<a href="%6$s">%2$d scheduled entries</a>, ', $stats['user_entry_scheduled_count']) .
			_n('<a href="%7$s">%3$d page draft</a>, ', '<a href="%7$s">%3$d page drafts</a>, ', $stats['page_draft_count']) .
			_n('and <a href="%8$s">%4$d comment awaiting approval</a>', 'and <a href="%8$s">%4$d comments awaiting approval</a>', $stats['unapproved_comment_count']),
			$stats['entry_draft_count'],
			$stats['user_entry_scheduled_count'],
			$stats['page_draft_count'],
			$stats['unapproved_comment_count'],
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ) ) ),
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'scheduled' ) ) ),
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'page' ), 'status' => Post::status( 'draft' ) ) ),
			URL::get( 'admin', array( 'page' => 'comments', 'search_status' => Comment::STATUS_UNAPPROVED ) )
		);
		?>
		</p>
</div>

<div class="container dashboard transparent">

	<ul class="modules">
		<?php foreach($modules as $modulename => $module): ?>
		<li class="module <?php echo $modulename; ?>module" id=<?php echo $modulename; ?>module">
			<?php echo $module; ?>
		</li>
		<?php endforeach; ?>
	</ul>

</div>


<?php include( 'footer.php' ); ?>
