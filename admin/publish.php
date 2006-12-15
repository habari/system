<div id="content-area">
	<div id="left-column">
		<br />
		<?php
		if ( isset( $settings['result'] ) ) {
			switch( $settings['result'] ) {
			case 'success':
				_e('<p>Your post has been saved.</p>');
				break;
			}
		}
		if( isset( $settings['slug'] ) ) {
			$post = Post::get( array( 'slug' => $settings['slug'], 'status' => Post::STATUS_ANY ) );
		}
		else {
			$post = new Post();
		}
		?>
		<form name="create-content" id="create-content" method="post" action="<?php URL::out('admin', 'page=publish'); ?>">
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
				<p><label><input type="radio" name="status" id="draft" value="<?php echo Post::STATUS_DRAFT; ?>" <?php echo ($post->status == Post::STATUS_DRAFT) ? 'checked="checked"' : ''; ?> >Draft</label><br />
				<label><input type="radio" name="status" id="publish" value="<?php echo Post::STATUS_PUBLISHED; ?>" <?php echo ($post->status == Post::STATUS_PUBLISHED) ? 'checked="checked"' : ''; ?> >Published</label></p>
			</div>
			<?php if($post->slug != '') : ?>
			<input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>" />
			<?php endif; ?>
		</form>
</div>
