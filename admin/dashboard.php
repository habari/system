<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-column" id="left-column">
		<div class="dashboard-block c3" id="welcome">
			<h1>Welcome back, <?php echo User::identify()->username; ?>!</h1>
			<p>Good to see you round these parts again.&nbsp; Before you get back to creating your masterpiece of blogginess, you might want to take a moment to catch up on things around <?php Options::out('title'); ?>.</p>
		</div>
		<div class="dashboard-block" id="stats">
			<h4>Site Statistics</h4>
				<ul id="site-stats">
					<li><span class="right">567</span> Visits Today</li>
					<li><span class="right">10067</span> Visits Past Week</li>
					<li><span class="right"><?php echo Posts::count_total(); ?></span> Total Posts</li>
					<li><span class="right"><?php echo Posts::count_by_author( User::identify()->id ); ?></span> Number of Your Posts</li>
					<li><span class="right"><?php echo Comments::count_total(); ?></span> Number of Comments</li>			
				</ul>
		</div>
		<div class="dashboard-block" id="system-info">
			<h4>System Health</h4>
			<ul>
				<li>&raquo; You are running Habari <?php Options::out('version'); ?>.</li>
				<li>&raquo; An <a href="#" title="Go to the release notes">Update for Habari</a> is available.</li>
				<li>&raquo; There are <a href="plugins" title="Plugin updates">3 Updates for Plugins</a> available.</li>
				<li>&raquo; An <a href="#" title="Download the updated theme">Update for your Theme</a> is available</li>
			</ul>
		</div>
		<div class="dashboard-block" id="incoming">
			<h4>Incoming Links (<a href="http://blogsearch.google.com/?scoring=d&num=10&q=link:<?php Options::out('hostname') ?>" title="More incoming links">more</a> &raquo;)</h4>
			<?php
			// This should be fetched on a pseudo-cron and cached: 
			$search = new RemoteRequest('http://blogsearch.google.com/blogsearch_feeds?scoring=d&num=10&output=atom&q=link:' . Options::get('hostname') );
			$search->execute();
			$xml = new SimpleXMLElement($search->get_response_body());
			if(count($xml->entry) == 0) {
				_e('<p>No incoming links were found to this site.</p>'); 
			}
			else {
			?>
			<ul id="incoming-links">
				<?php foreach($xml->entry as $entry) { ?>
				<li>
					<!-- need favicon discovery and caching here: img class="favicon" src="http://skippy.net/blog/favicon.ico" alt="favicon" / --> 
					<a href="<?php echo $entry->link['href']; ?>" title="<?php echo $entry->title; ?>"><?php echo $entry->title; ?></a>
				</li>
				<?php } ?>
			</ul>
			<?php
			}
			?>
		</div>
		<div class="dashboard-block c2" id="drafts">
				<h4>Drafts (<a href="manage/drafts" title="View Your Drafts">more</a> &raquo;)</h4>
				<ul id="site-drafts">
				<?php 
					if ( Posts::count_total( Post::STATUS_DRAFT ) ) {
						foreach ( Posts::by_status( Post::STATUS_DRAFT ) as $draft ) {
				?>
					<li>
						<span class="right">
							<a href="<?php echo $draft->permalink; ?>" title="View <?php echo $draft->title; ?>">
								<img src="<?php Options::out('base_url'); ?>system/admin/images/view.png" alt="View this draft" />
							</a>
							<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="Edit <?php echo $draft->title; ?>">
								<img src="<?php Options::out('base_url'); ?>system/admin/images/edit.png" alt="Edit this draft" />
							</a>
						</span>
						<?php echo $draft->title; ?>
					</li>
				<?php
						}
				?>
				</ul>
				<?php
					}
					else {
						_e('<p>There are currently no drafts in process</p>');
					}
				?>
		</div>
		<div class="dashboard-block c2" id="recent-comments">
			<h4>Recent Comments 
				<?php
					if ( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) {
				?>
				(<a href="<?php URL::out('admin', array('page'=>'moderate', 'option'=>'comments'));?>" title="View Comments Awaiting Moderation "><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?> comments awaiting moderation</a> &raquo;)
				<?php
					}
				?>
			</h4>
			<?php
				if ( Comments::count_total( Comment::STATUS_APPROVED ) ) {
			?>
				<table name="comment-data" width="100%" cellspacing="0">
					<thead>
						<tr>
							<th colspan="1" align="left">Post</th>
							<th colspan="1" align="left">Name</th>
							<th colspan="1" align="left">URL</th>
							<th colspan="1" align="center">Action</th>
						</tr>
					</thead>
					<?php
						foreach ( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 5, 'orderby' => 'date DESC' ) ) as $recent ) {
							$post= Post::get( array( 'id' => $recent->post_id, ) );
					?>
					<tr>
						<td><?php echo $post->title; ?></td>
						<td><?php echo $recent->name; ?></td>
						<td><?php echo $recent->url; ?></td>
						<td align="center">
							<a href="<?php URL::out('display_posts_by_slug', array('slug'=>$post->slug) ); ?>" title="View this post"><img src="<?php Options::out('base_url'); ?>system/admin/images/view.png" alt="View this comment" /></a>
							<img src="<?php Options::out('base_url'); ?>system/admin/images/edit.png" alt="Edit this comment" />
						</td>
					</tr>
					<?php } ?>
				</table>
				<?php
				}
				else {
					_e('<p>There are no comments to display</p>');
				}
				?>
		</div>
	</div>
</div>
<?php include('footer.php'); ?>
