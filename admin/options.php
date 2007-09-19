<?php include('header.php');?>
<div class="container">
<hr>
	<div class="column span-24 first">
		<?php 
		if ( isset( $result ) ) {
			switch( $result ) {
				case 'success':
					_e('<p class="update">Your options have been updated.</p>');
					break;
			}
		}
		?>
		<h1>Manage Options</h1>
		<p>Below are the options currently set on your site.</p>

		<form name="form_options" id="form_options" action="<?php URL::out('admin', 'page=options'); ?>" method="post">
			<p><label for="title">Blog Title:</label>
			<input type="text" id="title" name="title" value="<?php Options::out('title'); ?>"></p>
			
			<p><label for="tagline">Blog Tag Line:</label>
			<input type="text" id="tagline" name="tagline" value="<?php Options::out('tagline'); ?>"></p>
			
			<p><label for="about">About:</label>
			<textarea id="about" name="about" rows="5" cols="114"><?php Options::out('about'); ?></textarea></p>
			
			<p><label for="pagination">Number of items per page:</label>
			<input type="text" id="pagination" name="pagination" value="<?php Options::out('pagination'); ?>"></p>
			
			<p><label><input type="checkbox" id="pingback_send" name="pingback_send" value="1"<?php echo (Options::get('pingback_send') == 1 ? ' checked' : ''); ?>> Send Pingbacks to URLs linked from posts:</label></p>
			
			<p><label><input type="checkbox" id="comments_require_id" name="comments_require_id" value="1"<?php echo (Options::get('comments_require_id') == 1 ? ' checked' : ''); ?>> Require comment authors to fill out name and e-mail address:</label></p>
			
			<p><input type="submit" id="submit_options" name="submit_options" value="Update Options!"></p>
		</form>
	</div>
</div>
<?php include('footer.php');?>
