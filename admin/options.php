<div id="content-area">
	<h3>Habari Options</h3>
	<p>Below are the options currently set on your site. You can also create new options if you need them with the second form.</p>
	<form name="update-options" id="update=options" action="<?php Options::e('base_url'); ?>admin/post/update-options">
		<p><label>Base URL:</label></p>
		<p><input type="text" value="<?php Options::e('base_url'); ?>"/></p>
		<p><label>Blog Title:</label></p>
		<p><input type="text" value="<?php Options::e('blog_title'); ?>"/></p>
		<p><label>Blog Tag Line:</label></p>
		<p><input type="text" value="<?php Options::e('blog_tagline'); ?>"/></p>
		<p><label>About Text:</label></p>
		<p><textarea><?php Options::e('about'); ?></textarea></p>
		<p><input type="submit" value="Update Options!" /></p>
	</form>
	
	<h3>Add an Option</h3>
	<p>Use the form below to add a new option to Habari.</p>
	<form name="add-options" id="add-options" action="<?php Options::e('base_url'); ?>/admin/post/add-options">
		<p><label>Option Name</label></p>
		<p><input type="text" value="" /></p>
		<p><label>Option Value</label></p>
		<p><input type="text" value="" /></p>
		<p><input type="submit" value="Add Option!" /></p>
	</form>
</div>
