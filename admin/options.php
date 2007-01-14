<div id="content-area">
	<div class="dashbox c3" id="welcome">
		<h1>Manage Options</h1>
		<p>Below are the options currently set on your site.</p>
	<?php 
	if ( isset( $settings['result'] ) ) {
		switch( URL::o()->settings['result'] ) {
		case 'success':
			_e('<p>Your options have been updated.</p>');
			break;
		}
	}
	?>
	<form name="update-options" id="update-options" action="<?php URL::out('admin', 'page=options'); ?>" method="post">
		<p><label>Blog Title:</label></p>
		<p><input type="text" name="title" value="<?php Options::out('title'); ?>"/></p>
		<p><label>Blog Tag Line:</label></p>
		<p><input type="text" name="tagline" value="<?php Options::out('tagline'); ?>"/></p>
		<p><label>About</label></p>
		<p><textarea id="about" name="about"><?php Options::out('about'); ?></textarea></p>
		<p>Number of items per page:</p>
		<p><input type="text" name="pagination" value="<?php Options::out('pagination'); ?>" /></p>
		<p><label>Theme</label></p>
		<p><input type="text" name="theme_dir" value="<?php Options::out('theme_dir'); ?>" /></p>
		<p><input type="submit" value="Update Options!" /></p>
	</form>
</div>
</div>
