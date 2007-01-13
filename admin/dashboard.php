<div id="content-area">
	<div class="dashbox c3" id="welcome">
		<h1>Welcome, <?php echo User::identify()->username; ?>!</h1>
		<p>Good to see you round these parts.&nbsp; Before you get into creating your masterpiece of blogginess, you might want to take a moment to catch up on things around <?php Options::out('title'); ?>.</p>
	</div>
	<div class="dashbox r" id="stats">
		<h4>Site Statistics</h4>
		<table>
			<colgroup>
				<col class="label" />
				<col class="value" /> 
			</colgroup>
			<tr><td>Visits Today</td><td>567</td></tr>
			<tr><td>Visits Past Week</td><td>10067</td></tr>
			<tr><td>Total Posts</td><td><?php echo Posts::count_total(); ?></td></tr>
			<tr><td>Your Post Count</td><td><?php echo Posts::count_by_author( User::identify()->id ); ?></td></tr>
			<tr><td>Number of Comments</td><td><?php echo Comments::count_total(); ?></td></tr>
			<tr><td>Number of Spam Comments (<a href="<?php Options::out('host_url'); ?>admin/spam" title="Manage Spam">manage &raquo;</a>)</td><td><?php echo Comments::count_total( Comment::STATUS_SPAM); ?></td></tr>
		</table>
	</div>
	<div class="dashbox" id="system-info">
		<h4>System Health</h4>
		<ul>
			<li>&raquo; You are running Habari <?php Options::out('version'); ?>.</li>
			<li>&raquo; A Habari <a href="http://habariblog.org/download" title="A habari Update is available">Update is Available</a>.</li>
			<li>&raquo; <a href="plugins" title="Updates Ready">3 plugins</a> have updates ready.</li>
			<li>&raquo; An <a href="http://www.chrisjdavis.org/download/believe2.zip" title="An Update is Available">Update is Available</a> for your Theme.</li>
		</ul>
	</div>
	<div class="dashbox" id="incoming">
		<h4>Incoming Links (<a href="http://technorati.com/search/<?php Options::out('hostname') ?>" title="More incoming links">more</a> &raquo;)</h4>
		<ul id="incoming-links">
			<li>
				<img src="http://drbacchus.com/journal/favicon.ico" alt="favicon" /> <a href="http://wooga.drbacchus.com/journal" title="Dr Bacchus' Journal">Dr Bacchus' Journal</a>
			</li>
			<li>
				<img src="http://skippy.net/blog/favicon.ico" alt="favicon" /> <a href="http://skippy.net" title="Skippy dot net">Skippy Dot Net</a>
			</li>
			<li>
				<img src="http://brokenkode.com/favicon.ico" alt="favicon" /> <a href="http://brokenkode.com" title="Broken Kode">Broken Kode</a>
			</li>
			<li>
				<img src="http://asymptomatic.net/favicon.ico" alt="favicon" /> <a href="http://asymptomatic.net/" title="Asymptomatic">Asymptomatic</a>
			</li>
			<li>
				<img src="http://www.chrisjdavis.org/favicon.ico" alt="favicon" /> <a href="http://www.chrisjdavis.org" title="Sillyness Spelled Wrong Intentionally">Sillyness Spelled Wrong Intentionally</a>
			</li>
		</ul>
	</div>
	<div class="dashbox c2" id="drafts">
			<h4>Drafts (<a href="manage/drafts" title="View Your Drafts">more</a> &raquo;)</h4>
			<ul id="site-drafts">
			<?php 
				if( Posts::count_total( Post::STATUS_DRAFT ) ) {
					foreach( Posts::by_status( Post::STATUS_DRAFT ) as $draft ) {
			?>
				<li>
					<span class="right">
						<a href="<?php echo $draft->permalink; ?>" title="View <?php echo $draft->title; ?>">
							<img src="<?php Options::out('host_url'); ?>system/admin/images/view.png" alt="View this draft" />
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="Edit <?php echo $draft->title; ?>">
							<img src="<?php Options::out('host_url'); ?>system/admin/images/edit.png" alt="Edit this draft" />
						</a>
					</span>
					<?php echo $draft->title; ?>
				</li>
			<?php } ?>
			<?php } else {
				_e('<li>There are currently no drafts in process</li>');
			} ?>
			</ul>
	</div>
	<div class="dashbox c3" id="recent-comments">
		<h4>Recent Comments 
			<?php if( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) { ?>
			(<a href="manage/comments" title="View Comments Awaiting Moderation "><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?> comments awaiting moderation</a> &raquo;)
			<?php } ?>
		</h4>
		<?php
		if( Comments::count_total( Comment::STATUS_APPROVED ) ) {
		?>
			<table id="comment-data" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left">Post</th>
						<th align="left">Name</th>
						<th align="left">URL</th>
						<th align="center">Action</th>
					</tr>
				</thead>
				<?php foreach( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 5, 'orderby' => 'date DESC' ) ) as $recent ) { ?>
				<tr>
					<td><?php echo $recent->post_slug; ?></td>
					<td><a href="mailto:<?php echo $recent->email; ?>"><?php echo $recent->name; ?></a></td>
					<td><?php if( $recent->url != '' ): ?>
					<a href="<?php echo $recent->url; ?>"><?php echo $recent->url; ?></a>
					<?php endif; ?></td>
					<td align="center">
						<a href="<?php Options::out('base_url'); ?><?php echo $recent->post_slug; ?>#comment-<?php echo $recent->id; ?>" title="View this post"><img src="<?php Options::out('host_url'); ?>system/admin/images/view.png" alt="View this comment" /></a>
						<img src="<?php Options::out('host_url'); ?>system/admin/images/edit.png" alt="Edit this comment" />
						<img src="<?php Options::out('host_url'); ?>system/admin/images/delete.png" alt="Delete this comment" />
					</td>
				</tr>
				<?php } ?>
			</table>
			<?php } else {
				_e('<p>There are no comments to display</p>');
			}?>
	</div>
</div>
