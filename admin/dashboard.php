<div id="content-area">
<h1>Create Content</h1>
<?php 
if ( isset( $settings['result'] ) )
{
	switch( URL::o()->settings['result'] ) {
	case 'success':
		_e('<p>Your post has been saved.</p>');
		break;
	}
}
?>
<form name="create-content" id="create-content" method="post" action="<?php URL::out('admin'); ?>">
	<p><label>Title</label></p>
	<p><input type="text" name="title" id="title" size="35" /></p>
	<p><label>Tags</label></p>
	<p><input type="text" name="tags" id="tags" size="35" />
	<p><label>Content</label></p>
	<textarea name="content" id="content" cols="100%" rows="10"></textarea>
	<p><input type="submit" name="submit" id="submit" value="Save!" /></p>
</form>
</div>
