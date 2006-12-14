<div id="content-area">
	<div id="left-column">
		<h1>Welcome back <?php echo User::identify()->username; ?>!</h1>
		<p>Good to see you round these parts again.&nbsp; Before you get back to creating your masterpiece of blogginess, you might want to take a moment to catch up on things around <?php Options::out('title'); ?>.</p>
		<div class="stats">
			<h4>Site Statistics</h4>
			<ul id="site-stats">
				<li class="even"><span class="right">567</span> Visits Today</li>
				<li class="odd"><span class="right">10067</span> Visits Past Week</li>
				<li class="even"><span class="right">667</span> Number of Posts</li>
				<li class="odd"><span class="right">3000</span> Number of Comments</li>			
			</ul>
		</div>
		<div class="drafts">
			<h4>Drafts (<a href="manage/drafts" title="View Your Drafts">more</a> &raquo;)</h4>
			<ul id="site-drafts">
				<li class="even">
					<span class="right">
						<img src="/system/admin/images/view.png" alt="View this draft" />
						<img src="/system/admin/images/edit.png" alt="Edit this draft" />
					</span>
					Draft One
				</li>
				<li class="odd">
					<span class="right">
						<img src="/system/admin/images/view.png" alt="View this draft" />
						<img src="/system/admin/images/edit.png" alt="Edit this draft" />
					</span>
					Draft Two
				</li>
				<li class="even">
					<span class="right">
						<img src="/system/admin/images/view.png" alt="View this draft" />
						<img src="/system/admin/images/edit.png" alt="Edit this draft" />
					</span>
					Draft Three
				</li>
				<li class="odd">
					<span class="right">
						<img src="/system/admin/images/view.png" alt="View this draft" />
						<img src="/system/admin/images/edit.png" alt="Edit this draft" />
					</span>
					Draft Four
				</li>
				<li class="even">
					<span class="right">
						<img src="/system/admin/images/view.png" alt="View this draft" />
						<img src="/system/admin/images/edit.png" alt="Edit this draft" />
					</span>
					Draft Five 
				</li>
			</ul>
		</div>
		<div class="recent-comments">
		<h4>Recent Comments (<a href="manage/comments" title="View Comments Awaiting Moderation ">5 comments awaiting moderation</a> &raquo;)</h4>
			<table name="comment-data" width="100%" cellspacing="0">
				<tr>
					<th colspan="1" align="left">Post</th>
					<th colspan="1" align="left">Name</th>
					<th colspan="1" align="left">URL</th>
					<th colspan="1" align="center">Action</th>
				</tr>
				<tr class="even">
					<td>Welcome to Habari!</td>
					<td>Chris J. Davis</td>
					<td>http://www.chrisjdavis.org</td>
					<td align="center">
						<img src="/system/admin/images/view.png" alt="View this comment" />
						<img src="/system/admin/images/edit.png" alt="Edit this comment" />
					</td>
				</tr>
				<tr class="odd">
					<td>Welcome to Habari!</td>
					<td>Chris J. Davis</td>
					<td>http://www.chrisjdavis.org</td>
					<td align="center">
						<img src="/system/admin/images/view.png" alt="View this comment" />
						<img src="/system/admin/images/edit.png" alt="Edit this comment" />
					</td>
				</tr>
				<tr class="even">
					<td>Welcome to Habari!</td>
					<td>Chris J. Davis</td>
					<td>http://www.chrisjdavis.org</td>
					<td align="center">
						<img src="/system/admin/images/view.png" alt="View this comment" />
						<img src="/system/admin/images/edit.png" alt="Edit this comment" />
					</td>
				</tr>
			</table>
		</div>
	</div>
	<div class="options">
		<ul id="options-list">
			<li> 
				<span class="right">
					<small>(<a href="#" title="">help</a>)</small>
				</span>
				<a href="<?php URL::out('admin', 'page=options'); ?>" title="Options">Options</a>
			</li>
			<li> 
				<span class="right">
					<small>(<a href="#" title="">help</a>)</small>
				</span>
				<a href="<?php URL::out('admin', 'page=plugins'); ?>" title="Plugins">Plugins</a>
			</li>
			<li>
				<span class="right">
					<small>(<a href="#" title="">help</a>)</small>
				</span>
				<a href="<?php URL::out('admin', 'page=themes'); ?>" title="Themes">Themes</a> 
			</li>
			<li>
				<span class="right">
					<small>(<a href="#" title="">help</a>)</small>
				</span>
				<a href="<?php URL::out('admin', 'page=users'); ?>" title="Users">Users</a> 
			</li>
		</ul>
	</div>
	<div class="system-info">
	<h4>About Habari</h4>
	<ul>
		<li>&raquo; You are running Habari <?php Options::out('version'); ?>.</li>
		<li>&raquo; A Habari <a href="http://habariblog.org/download" title="A habari Update is available">Update is Available</a>.</li>
		<li>&raquo; <a href="plugins" title="Updates Ready">3 plugins</a> have updates ready.</li>
		<li>&raquo; An <a href="http://www.chrisjdavis.org/download/believe2.zip" title="An Update is Available">Update is Available</a> for your Theme.</li>
	</ul>
	</div>
</div>
