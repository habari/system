<?php include('header.php');?>
<div id="content-area">
<div class="dashboard-block c3" id="welcome">
	<h1><?php _e('Habari Content'); ?></h1>
	<p><?php _e('Here you will find all the content you have created, ready to be tweaked, edited or removed.'); ?></p>
	<?php 
	if ( isset( $result ) ) {
		switch( $result ) {
			case 'success':
				echo '<p class="update">' . _t('Your options have been updated.') . '</p>';
				break;
		}
	}
	?>
	</div>
	<div class="dashboard-block c3" id="content-published">
		<h4><?php _e('Published Entries'); ?></h4>
			<table id="post-data-published" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left"><?php _e('Title'); ?></th>
						<th align="left"><?php _e('Author'); ?></th>
						<th align="left"><?php _e('Published'); ?></th>
						<th align="center"><?php _e('Action'); ?></th>
					</tr>
				</thead>
				<?php
				// we load the WSSE tokens here
				// for use in the delete button below
				$wsse= Utils::WSSE();
				foreach (Posts::get( array( 'content_type' => 'entry', 'status' => Post::status('published'), 'limit' => '25' ) ) as $post ) {
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
							<input type="image" src="<?php Site::out_url('admin_theme'); ?>/images/delete.png" name="delete" value="<?php _e('delete'); ?>">
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
		<h4 id="drafts"><?php _e('Entries Currently in Draft'); ?></h4>
			<table id="post-data-draft" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left"><?php _e('Title'); ?></th>
						<th align="left"><?php _e('Author'); ?></th>
						<th align="left"><?php _e('Published'); ?></th>
						<th align="center"><?php _e('Action'); ?></th>
					</tr>
				</thead>
				<?php 	foreach ( $drafts as $draft ) { ?>
				<tr>
					<td><?php echo $draft->title; ?></td>
					<td><?php echo $draft->author->username; ?></td>
					<td><?php echo $draft->pubdate; ?></td>
					<td align="center">
						<a href="<?php echo $draft->permalink; ?>" title="<?php _e('View this Entry'); ?>">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="<?php _e('View this entry'); ?>">
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="<?php _e('Edit this entry'); ?>">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="<?php _e('Edit this entry'); ?>">
						</a>
							<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
								<input type="hidden" name="slug" value="<?php echo $draft->slug; ?>">
								<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>">
								<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
								<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
								<input type="image" src="<?php Site::out_url('admin_theme'); ?>/images/delete.png" name="delete" value="<?php _e('delete'); ?>">
							</form>
					</td>
				</tr>
				<?php } ?>
				
			</table>
	</div>
	<?php endif; ?>
	<?php
	$pages= Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published'), 'nolimit' => 1 ) );
	if ( count( $pages ) > 0 ) : ?>
	<div class="dashboard-block c3" id="content-pages">
		<h4><?php _e('Paged Entries'); ?></h4>
			<table id="post-data-draft" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th align="left"><?php _e('Title'); ?></th>
						<th align="left"><?php _e('Author'); ?></th>
						<th align="left"><?php _e('Published'); ?></th>
						<th align="center"><?php _e('Action'); ?></th>
					</tr>
				</thead>
				<?php 	foreach ( $pages as $page ) { ?>
				<tr>
					<td><?php echo $page->title; ?></td>
					<td><?php echo $page->author->username; ?></td>
					<td><?php echo $page->pubdate; ?></td>
					<td align="center">
						<a href="<?php URL::out('display_posts_by_slug', array('slug'=>$page->slug)); ?>" title="<?php _e('View this Entry'); ?>">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/view.png" alt="<?php _e('View this entry'); ?>" />
						</a>
						<a href="<?php URL::out('admin', 'page=publish&slug=' . $page->slug); ?>" title="<?php _e('Edit this entry'); ?>">
							<img src="<?php Site::out_url('admin_theme'); ?>/images/edit.png" alt="E<?php _e('dit this entry'); ?>" />
						</a>
							<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
								<input type="hidden" name="slug" value="<?php echo $page->slug; ?>" />
								<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>" />
								<input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>" />
								<input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>" />
								<input type="image" src="<?php Site::out_url('admin_theme'); ?>/images/delete.png" name="delete" value="<?php _e('delete'); ?>" />
							</form>
					</td>
				</tr>
				<?php } ?>

			</table>
	</div>
	<?php endif; ?>
</div>
<?php include('footer.php');?>
