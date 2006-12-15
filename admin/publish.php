<div id="content-area">
	<div id="left-column">
		<br />
		<?php
		if ( isset( $settings['result'] ) ) {
			switch( URL::o()->settings['result'] ) {
			case 'success':
				_e('<p>Your post has been saved.</p>');
				break;
			}
		}
		?>
		<form name="create-content" id="create-content" method="post" action="<?php URL::out('admin'); ?>">
			<?php $post = Post::get( array('slug'=>$settings['slug'], 'status'=>Post::STATUS_ANY) ); ?>
			<div class="content">
				<h4>Title</h4>
				<input type="text" name="title" id="title" size="100%" value="<?php echo $post->title; ?>" />
				<h4>Content</h4>
				<textarea name="content" id="content" rows="10"><?php echo $post->content; ?></textarea>
				<p class="right"><input type="submit" name="submit" id="submit" value="Save!" /></p>
			</div>
	</div>
			<div class="metadata">
				<h4>Tags</h4>
				<input type="text" name="tags" id="tags" size="35%" />
				<h4>Status</h4>
				<p><input type="radio" name="draft" id="draft" value="0" checked>Save as Draft<br />
				<input type="radio" name="publish" id="publish" value="1" >Publish</p>
			</div>
		</form>
</div>
