<?php include( 'header.php' ); ?>


<div class="container dashboardinfo transparent">
		<p>
		<?php
		printf(_t(Options::get('title'). ' has been active for %1$d years, %2$d months and %3$d days.'), $active_time['years'], $active_time['months'], $active_time['days']);
		?>
		<br>
		<?php
		printf(
			_t('The <a href="' . URL::get( 'admin', 'page=users' ) . '">%1$d authors</a> have published <a href="' . URL::get( 'admin', 'page=content&type=2' ) . '">%2$d pages</a> and <a href="' . URL::get( 'admin', 'page=content&type=1' ) . '">%3$d entries</a> with <a href="' . URL::get( 'admin', 'page=moderate&search_status=1' ) . '">%4$d comments</a> and <a href="' . URL::get( 'admin', 'page=tags' ) . '">%5$d tags</a>'),
			$stats['author_count'],
			$stats['page_count'],
			$stats['entry_count'],
			$stats['comment_count'],
			$stats['tag_count']
		);
		?></p>
		<p><?php
		printf(
			_t('You currently have <a href="' . URL::get( 'admin', 'page=content&type=1&status=1' ) . '">%1$d entry drafts</a>, <a href="' . URL::get( 'admin', 'page=content&type=1&status=1' ) . '">%2$d page drafts</a> and <a href="' . URL::get( 'admin', 'page=moderate&show=unapproved' ) . '">%3$d comments awaiting approval</a>'),
			$stats['page_draft_count'],
			$stats['entry_draft_count'],
			$stats['unapproved_comment_count']
		);
		?>
		</p>
</div>

<div class="container dashboard transparent">

	<ul class="modules">
		<li class="module latestentriesmodule" id="latestentriesmodule">
			
			<div class="options">&nbsp;</div>

			<div class="modulecore">
				<h2>Latest Entries</h2>

				<div class="handle">&nbsp;</div>

				<ul class="items">

					<?php foreach($recent_posts as $post): ?>
					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="<?php printf(_t('Posted at %1$s'), date('h.m on F jS, Y', strtotime($post->pubdate))); ?>"><?php echo date('M j', strtotime($post->pubdate)); ?></a></span>
						<span class="title pct75"><a href="<?php echo $post->permalink; ?>"><?php echo $post->title; ?></a> <a class="minor" href="<?php Site::out_url('habari'); ?>/admin/user/<?php echo $post->author->username; ?>">by <?php echo $post->author->username; ?></a></span>
						<span class="comments pct10"><a href="#"><?php echo $post->comments->approved->count; ?></a></span>
					</li>
					<?php endforeach; ?>

				</ul>
			</div>

			<div class="optionswindow">
				<h2>Latest Entries</h2>

				<div class="handle">&nbsp;</div>

				<div class="optionscontent">
					<p>
						<label for="dummy" class="pct30"># of Entries</label>
						<select class="pct55">
							<option>10</option>
						</select>
					</p>

					<p class="buttons">
						<input type="submit" value="Submit">
					</p>
				</div>
			</div>
		</li>


		<li class="module latestcommentsmodule" id="latestcommentsmodule">
			
			<div class="options">&nbsp;</div>

			<div class="modulecore">
				<h2>Latest Comments</h2>

				<div class="handle">&nbsp;</div>

				<ul class="items">

					<?php foreach( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 10, 'orderby' => 'date DESC' ) ) as $comment ): ?>
					<li class="item clear">
						<span class="titleanddate pct85"><a href="<?php echo $comment->post->permalink; ?>" class="title"><?php echo $comment->post->title; ?></a> <a href="#" class="date minor"><?php echo date('M j', strtotime($comment->post->pubdate)); ?></a></span>
						<span class="comments pct15"><a href="<?php echo $comment->post->permalink; ?>#comments" title="<?php printf(_n('%1$d comment', '%1$d comments', $comment->post->comments->approved->comments->count), $comment->post->comments->approved->comments->count); ?>"><?php echo $comment->post->comments->approved->comments->count; ?></a></span>
						<ul class="commentauthors pct85 minor">
							<?php foreach($comment->post->comments->comments->approved as $comment): ?>
							<li><a href="<?php echo $comment->post->permalink; ?>#comment_<?php echo $comment->id; ?>" title="<?php printf(_t('Posted at %1$s'), date('h.m on F jS, Y', strtotime($comment->pubdate))); ?>" class="opa100"><?php echo $comment->name; ?></a></li>
							<?php endforeach; ?>
						</ul>
					</li>
					<?php endforeach; ?>
				
				</ul>

			</div>


			<div class="optionswindow">
				<h2>Latest Comments</h2>

				<div class="handle">&nbsp;</div>

				<div class="optionscontent">
					<p>
						<label for="dummy" class="pct30"># of Entries</label>
						<select class="pct55">
							<option>10</option>
						</select>
					</p>

					<p class="buttons">
						<input type="submit" value="Submit">
					</p>
				</div>
			</div>
		</li>


		<li class="module feedmodule" id="habarideveloperblog">

			<div class="options">&nbsp;</div>

			<div class="modulecore">
				<h2>Habari Developer Blog</h2>

				<div class="handle">&nbsp;</div>

				<ul class="items">
				
					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 14.15 on February 8th, 2008">Feb 8</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">0.4 Released</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 14.15 on February 7th, 2008">Feb 7</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Flashback: Jan 31-Feb 7</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 14.15 on February 2nd, 2008">Feb 2</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Bug Hunt- 2 February 2008</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 14.15 on January 29th, 2008">Jan 29</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Flashback: January 15-30, 2008</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 18.15 on January 29th, 2008">Jan 29</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Flashback Jan 10-14, 2007</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 11.39 on January 29th, 2008">Jan 29</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Why Habari Rocks</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 01.15 on January 20th, 2008">Jan 29</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Why Habari Rocks</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

					<li class="item clear">
						<span class="date pct15 minor"><a href="#" title="Posted at 09.15 on January 18th, 2008">Jan 18</a></span>
						<span class="titleandauthor pct85"><a href="#" class="title">Why Habari Rocks</a> <a href="#" class="author minor">by Michael</a></span>
					</li>

				</ul>

			</div>


			<div class="optionswindow">
				<h2>Feed</h2>

				<div class="handle">&nbsp;</div>

				<div class="optionscontent">
					<p>
						<label for="dummy" class="pct30"># of Entries</label>
						<select class="pct55">
							<option>10</option>
						</select>
					</p>

					<p class="buttons">
						<input type="submit" value="Submit">
					</p>
				</div>
			</div>
		</li>

		<li class="module" id="additem">
			<div class="modulecore">
				<h2>Add Item</h2>
				<form>
					<select><option>Feed</option></select>
					<input type="button" value="+">
				</form>
			</div>
		</li>
	</ul>
	
</div>


<?php include( 'footer.php' ); ?>
