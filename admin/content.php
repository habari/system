<?php include('header.php');?>
<div id="content-area">
<div class="dashboard-block c3" id="welcome">
	<h1>Habari Content</h1>
	<p>Post, pages, images and podcasts.&nbsp; Here you will find all the content you have created, ready to be tweaked, edited or removed.</p>
	<?php 
	if ( isset( $settings['result'] ) )
	{
		switch( URL::o()->settings['result'] ) {
		case 'success':
			_e('<p>Your options have been updated.</p>');
			break;
		}
	}
	?>
	</div>
	<div class="dashboard-block c3" id="content-published">
		<h4>Published Entries</h4>
			<table id="post-data-published" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left">Title</th>
						<th align="left">Author</th>
						<th align="left">Published</th>
						<th align="center">Action</th>
					</tr>
				</thead>
				<?php 	foreach ( Posts::get( array( 'limit' => '10' ) ) as $post ) { ?>
				<tr>
					<td><?php echo '<a href="' . $post->permalink . '">' . $post->title ?></a></td>
					<td><?php echo $post->author->username ?></td>
					<td><?php echo $post->pubdate ?></td>
					<td align="center">
						<a href="<?php echo $post->permalink ?>" title="View this Entry">
							<img src="<?php Options::out('base_url'); ?>/system/admin/images/view.png" alt="View this entry" />
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>" title="Edit this entry">
							<img src="<?php Options::out('base_url'); ?>/system/admin/images/edit.png" alt="Edit this entry" />
						</a>
						<img src="<?php Options::out('base_url'); ?>/system/admin/images/delete.png" alt="Delete this comment" />
					</td>
				</tr>
				<?php } ?>
			</table>
	</div>
	<div class="dashboard-block c3" id="content-draft">
		<h4>Entries Currently in Draft</h4>
			<table id="post-data-draft" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left">Title</th>
						<th align="left">Author</th>
						<th align="left">Published</th>
						<th align="center">Action</th>
					</tr>
				</thead>
				<?php 	foreach ( Posts::get( array( 'limit' => '10', 'status'  => Post::STATUS_DRAFT ) ) as $draft ) { ?>
				<tr>
					<td><?php echo $draft->title; ?></td>
					<td><a href="<?php Options::out('base_url'); ?><?php echo $post->username; ?>"><?php echo $draft->username; ?></a></td>
					<td><?php echo $draft->pubdate; ?></td>
					<td align="center">
						<a href="<?php Options::out('base_url'); ?><?php echo $draft->post_slug; ?>" title="View this Entry">
							<img src="<?php Options::out('base_url'); ?>system/admin/images/view.png" alt="View this entry" />
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="Edit this entry">
							<img src="<?php Options::out('base_url'); ?>system/admin/images/edit.png" alt="Edit this entry" />
						</a>
						<img src="<?php Options::out('base_url'); ?>system/admin/images/delete.png" alt="Delete this comment" />
					</td>
				</tr>
				<?php } ?>
			</table>
	</div>
</div>
<?php include('footer.php');?>