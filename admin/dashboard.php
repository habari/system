<div id="content-area">
<h1>Create Content</h1>
<form name="create-content" id="create-content" method="post" action="<?php Options::out('base_url'); ?>admin/process/add_post">
	<p><label>Title</label></p>
	<p><input type="text" name="title" id="title" size="35" /></p>
	<p><label>Tags</label></p>
	<p><input type="text" name="tags" id="tags" size="35" />
	<p><label>Content</label></p>
	<textarea name="content" id="content" cols="100%" rows="10"></textarea>
	<p><input type="submit" name="submit" id="submit" value="Save!" /></p>
</form>
</div>