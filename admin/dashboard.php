<?php include( 'header.php' ); ?>


<div class="container dashboardinfo transparent">
		<p>
		<?php
		printf(_t(Options::get('title'). ' has been active for %1$d years, %2$d months and %3$d days.'), $active_time['years'], $active_time['months'], $active_time['days']);
		?>
		<br>
		<?php
		printf(
			_t('The <a href="%6$s">%1$d authors</a> have published <a href="%7$s">%2$d pages</a> and <a href="%8$s">%3$d entries</a> with <a href="%9$s">%4$d comments</a> and <a href="%10$s">%5$d tags</a>'),
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
			_t('You currently have <a href="%4$s">%1$d entry drafts</a>, <a href="%5$s">%2$d page drafts</a> and <a href="%6$s">%3$d comments awaiting approval</a>'),
			$stats['entry_draft_count'],
			$stats['page_draft_count'],
			$stats['unapproved_comment_count'],
			URL::get( 'admin', array( 'page' => 'entries', 'type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ) ) ),
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
