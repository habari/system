<div id="content-area">
	<h3>Habari Options</h3>
	<p>Below are the options currently set on your site.</p>
	<form name="update-options" id="update-options" action="<?php Options::out('base_url'); ?>admin/post/options" method="post">
		<p><label>Blog Title:</label></p>
		<p><input type="text" name="title" value="<?php Options::out('title'); ?>"/></p>
		<p><label>Blog Tag Line:</label></p>
		<p><input type="text" name="tagline" value="<?php Options::out('tagline'); ?>"/></p>
		<p><input type="submit" value="Update Options!" /></p>
	</form>
</div>
