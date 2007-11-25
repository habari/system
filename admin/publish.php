<?php include('header.php'); ?>
<div class="container">
<hr>
 <div class="column prepend-3 span-18 append-3">
  <?php
	Session::messages_out();
  if ( isset( $slug ) ) {
		$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
		$tags= htmlspecialchars( Utils::implode_quoted( ',', $post->tags ) );
		$content_type= Post::type( $post->content_type );
?>
<a href="<?php echo $post->permalink; ?>">View Post</a>
<?php
  } else {
		$post= new Post();
		$tags= '';
		$content_type= Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );
	}
	?>
  <form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">
    <p><label for="title" class="incontent"><?php _e('Title'); ?></label><input type="text" name="title" id="title" class="bigtext" size="100%" value="<?php if ( !empty($post->title) ) { echo $post->title; } ?>"></p>

    <p><label for="content" class="incontent"><?php _e('Content'); ?></label><textarea name="content" id="content" rows="20" cols="114" class="resizable bigtext"><?php if ( !empty($post->content) ) { echo htmlspecialchars($post->content); } ?></textarea></p>

    <p><label for="tags" class="incontent"><?php _e('Tags - Comma Separated')?></label><input type="text" name="tags" id="tags" class="bigtext" value="<?php if ( !empty( $tags ) ) { echo $tags; } ?>"></p>

    <h5 class="center">Post Details</h5>
 </div>
	<div class="column prepend-3 span-8 first">
	<h5><?php _e('Meta Information'); ?></h5>
	<p><?php _e('Publish Date:'); ?> <input type="text" name="pubdate" id="pubdate" value="<?php echo $post->pubdate; ?>"></p>
	<p><?php _e('Content Address:'); ?> <input type="text" name="newslug" id="newslug" value="<?php echo $post->slug; ?>"></p>
	</div>
	<div class="column span-10  last">
	<h5><?php _e('Entry Settings'); ?></h5>
	<ul>
	 <?php
	 // pass "false" to list_post_statuses() so that we don't
	 // include internal post statuses
	 $statuses= Post::list_post_statuses( false );
	 unset( $statuses[array_search( 'any', $statuses )] );
	 $statuses= Plugins::filter('admin_publish_list_post_statuses', $statuses);
	 ?>
	 <li><label><?php echo Utils::html_select( 'status', array_flip($statuses), $post->status, array( 'class'=>'longselect') ); ?></label></li>
	</ul>

	<h5><?php _e('Comments'); ?></h5>
	<ul>
	 <li><label><input type="radio" name="comments_disabled" value="0" <?php echo ( $post->info->comments_disabled == 0 ) ? 'checked' : ''; ?>> <?php _e('Allow comments'); ?></label></li>
	 <li><label><input type="radio" name="comments_disabled" value="1" <?php echo ( $post->info->comments_disabled == 1 ) ? 'checked' : ''; ?>> <?php _e('No comments'); ?></label></li>
	</ul>
	<p><input type="hidden" name="content_type" value="<?php echo $content_type; ?>"></p>
	<p><input type="submit" name="submit" id="submit" value="<?php _e('Save!'); ?>"></p>

	<?php if ( $post->slug != '' ) { ?>
	<p><input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>"></p>
	<?php } ?>
	</form>
	</div>

</div>
<?php include('footer.php'); ?>
