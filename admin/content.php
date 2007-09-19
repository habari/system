<?php include('header.php');?>
<div class="container">
<hr>
<div class="column span-24 first" id="welcome">
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
	<div class="column span-24" id="content-published">
		<h3><?php _e('Published Entries'); ?></h3>
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
						<a class="view" href="<?php echo $post->permalink ?>" title="View this Entry">
							View
						</a>
						<a class="edit" href="<?php URL::out('admin', 'page=publish&slug=' . $post->slug); ?>" title="Edit this entry">
							Edit
						</a>
						<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
							<p><input type="hidden" name="slug" value="<?php echo $post->slug; ?>"></p>
							<p><input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"></p>
							<p><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"></p>
							<p><input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>"></p>
							<p><button name="delete"><?php _e('Delete'); ?></button></p>
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
	<div class="column span-24 " id="content-draft">
		<h3 id="drafts"><?php _e('Entries Currently in Draft'); ?></h3>
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
						<a class="view" href="<?php echo $draft->permalink; ?>" title="<?php _e('View this Entry'); ?>">
							View
						</a>
						<a class="edit" href="<?php URL::out('admin', 'page=publish&slug=' . $draft->slug); ?>" title="<?php _e('Edit this entry'); ?>">
							Edit
						</a>
							<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
								<p><input type="hidden" name="slug" value="<?php echo $draft->slug; ?>"></p>
								<p><input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"></p>
								<p><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"></p>
								<p><input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>"></p>
								<p><button name="delete"><?php _e('Delete'); ?></button></p>
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
	<div class="column span-24" id="content-pages">
		<h3><?php _e('Paged Entries'); ?></h3>
			<table id="page-data-published" width="100%" cellspacing="0">
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
						<a class="view" href="<?php URL::out('display_posts_by_slug', array('slug'=>$page->slug)); ?>" title="<?php _e('View this Entry'); ?>">
							View
						</a>
						<a class="edit" href="<?php URL::out('admin', 'page=publish&slug=' . $page->slug); ?>" title="<?php _e('Edit this entry'); ?>">
							Edit
						</a>
							<form method="post" action="<?php  URL::out( 'admin', 'page=delete_post' ); ?>" class="buttonform">
								<p><input type="hidden" name="slug" value="<?php echo $page->slug; ?>"></p>
								<p><input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"></p>
								<p><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"></p>
								<p><input type="hidden" name="PasswordDigest" value="<?php echo $wsse['digest']; ?>"></p>
								<p><button name="delete"><?php _e('Delete'); ?></button></p>
							</form>
					</td>
				</tr>
				<?php } ?>

			</table>
	</div>
	<?php endif; ?>
</div>
<?php include('footer.php');?>
