<div id="content-area">
	<div class="dashbox c3" id="welcome">
	<?php
		if ( isset( $settings['result'] ) ) {
			switch( $settings['result'] ) {
				case 'success':
				_e('<p class="update">Your post has been saved.</p>');
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
		<div class="dashbox c3 publish">
			<h4>Title</h4>
			<input type="text" name="title" id="title" size="100%" value="<?php echo $post->title; ?>" />

			<h4>Content</h4>
			<textarea name="content" id="content" rows="20"><?php echo $post->content; ?></textarea>
			
			<h4>Tags</h4>
			<div id="tagbox">
				<input type="text" name="tags" class="right" id="tags" />
				<p>Type a new tag or select from the list of existing tags below:</p>
			</div>

			<h4>Page Settings</h4>
			<ul>
				<li><label><input type="radio" name="status" id="draft" value="<?php echo Post::STATUS_DRAFT; ?>" <?php echo ($post->status == Post::STATUS_DRAFT) ? 'checked="checked"' : ''; ?> >Draft</label></li>
				<li><label><input type="radio" name="status" id="publish" value="<?php echo Post::STATUS_PUBLISHED; ?>" <?php echo ($post->status == Post::STATUS_PUBLISHED) ? 'checked="checked"' : ''; ?> >Published</label></li>
			</ul>
			<p class="right"><input type="submit" name="submit" id="submit" value="Save!" /></p>
		</div>
		<?php if($post->slug != '') : ?>
		<input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>" />
		<?php endif; ?>
	</form>
	</div>
</div>
