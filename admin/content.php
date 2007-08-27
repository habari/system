<?php include('header.php');?>
<div id="content-area">
<div class="dashboard-block c3" id="welcome">
	<h1>Habari Content</h1>
	<p>Post, pages, images and podcasts.&nbsp; Here you will find all the content you have created, ready to be tweaked, edited or removed.</p>
	<?php 
	if ( isset( $result ) ) {
		switch( $result ) {
			case 'success':
				_e('<p class="update">Your options have been updated.</p>');
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
				<?php
				// we load the WSSE tokens here
				// for use in the delete button below
				$wsse= Utils::WSSE();
				foreach ( Posts::get( array( 'limit' => '10', 'status'  => Post::status('published') ) ) as $post ) {
				?>
				<tr>
					<td><?php echo '<a href="' . $post->permalink . '">' . $post->title ?></a></td>
					<td><?php echo $post->author->username ?></td>
					<td><?php echo $post->pubdate ?></td>
					<td align="center">
						<a href="<?php echo $post->permalink ?>" title="View this Entry">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="View this entry">
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>" title="Edit this entry">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="Edit this entry">
						</a>
						<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
							<input type="hidden" name="slug" value="<?php echo $post->slug; ?>">
							<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
							<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
							<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
							<input type="image" src="<?php Site::out_url('admin_theme'); ?>/images/delete.png" name="delete" value="delete">
						</form>
					</td>
				</tr>
				<?php
				}
				?>
			</table>
	</div>
	<?php
	$drafts= Posts::get( array( 'limit' => '10', 'status'  => Post::status('draft') ) );
	if ( count( $drafts ) > 0 ) : ?>
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
				<?php 	foreach ( $drafts as $draft ) { ?>
				<tr>
					<td><?php echo $draft->title; ?></td>
					<td><?php echo $draft->author->username; ?></td>
					<td><?php echo $draft->pubdate; ?></td>
					<td align="center">
						<a href="<?php echo $draft->permalink; ?>" title="View this Entry">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="View this entry">
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="Edit this entry">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="Edit this entry">
						</a>
							<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
								<input type="hidden" name="slug" value="<?php echo $draft->slug; ?>">
								<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
								<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
								<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
								<input type="image" src="<?php Site::out_url('admin_theme'); ?>/images/delete.png" name="delete" value="delete">
							</form>
					</td>
				</tr>
				<?php } ?>
			</table>
	</div>
	<?php endif; ?>
</div>
<?php include('footer.php');?>
