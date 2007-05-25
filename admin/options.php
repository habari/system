<?php include('header.php');?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
		<h1>Manage Options</h1>
		<p>Below are the options currently set on your site.</p>
	<?php 
	if ( isset( $result ) ) {
		switch( $result ) {
			case 'success':
				_e('<p class="update">Your options have been updated.</p>');
				break;
		}
	}
	?>
	<form name="update-options" id="update-options" action="<?php URL::out('admin', 'page=options'); ?>" method="post">
		<p><label>Blog Title:</label></p>
		<p><input type="text" name="title" value="<?php Options::out('title'); ?>"/></p>
		
		<p><label>Blog Tag Line:</label></p>
		<p><input type="text" name="tagline" value="<?php Options::out('tagline'); ?>"/></p>
		
		<p><label>About:</label></p>
		<p><textarea id="about" name="about"><?php Options::out('about'); ?></textarea></p>
		
		<p><label>Number of items per page:</label></p>
		<p><input type="text" name="pagination" value="<?php Options::out('pagination'); ?>" /></p>
		
		<p><label>Send Pingbacks to URLs linked from posts:</label></p>
		<p><input type="checkbox" name="pingback_send" value="1"<?php echo (Options::get('pingback_send') == 1 ? ' checked="checked"' : ''); ?> /></p>
		
		<p><label>Require comment authors to fill out name and e-mail address:</label></p>
		<p><input type="checkbox" name="comments_require_id" value="1"<?php echo (Options::get('comments_require_id') == 1 ? ' checked="checked"' : ''); ?> /></p>
		
		<p><input type="submit" value="Update Options!" /></p>
	</form>
</div>
</div>
<?php include('footer.php');?>
