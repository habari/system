<?php include('header.php'); ?>
<div id="content-area">
 <div class="dashboard-block c3" id="welcome">
  <?php
  if ( isset( $result ) ) {
   switch( $result ) {
    case 'success':
     _e('<p class="update">Your post has been saved.</p>');
     break;
   }
  }
  if ( isset( $slug ) ) {
   $post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
   $tags= htmlspecialchars( Utils::implode_quoted( ',', $post->tags ) );
   $content_type= Post::type( $post->content_type );
  } else {
   $post= new Post();
   $tags= array();
			$content_type= Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );
  }
   ?>
  <form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">
   <div class="dashboard-block c3 publish">
    <h4>Title</h4>
    <input type="text" name="title" id="title" size="100%" value="<?php echo $post->title; ?>">
    
    <h4>Content</h4>
    <textarea name="content" id="content" rows="20"><?php echo htmlspecialchars( $post->content ); ?></textarea>
    
    <h4>Tags</h4>
    <p>Seperate tags with a comma, space seperated words become multi-world tags. <input type="text" name="tags" id="tags" value="<?php echo ( !empty( $tags ) ) ? $tags : ''; ?>"></p>
    
    <h4>Meta Information</h4>
    <p>Publish Date: <input type="text" name="pubdate" id="pubdate" value="<?php echo $post->pubdate; ?>"></p>
    <p>Content Address: <input type="text" name="newslug" id="newslug" value="<?php echo $post->slug; ?>"></p>
    
    <h4>Page Settings</h4>
    <ul>
     <?php
     $statuses= Post::list_post_statuses();
     $statuses= Plugins::filter('admin_publish_list_post_statuses', $statuses);
     foreach ( $statuses as $name => $value ) {
      if ( 'any' == $name ) {
       continue;
      }
					$post_status= Post::status( $name );
     ?>
     <li><label><input type="radio" name="status" id="<?php echo $name; ?>" value="<?php echo $post_status; ?>" <?php echo ( $post->status == $post_status ) ? 'checked' : ''; ?> > <?php echo ucwords( $name ); ?></label></li>
     <?php
     }
     ?>
    </ul>
    
    <h4>Comments</h4>
    <ul>
     <li><label><input type="radio" name="comments_disabled" id="comments_enabled" value="0" <?php echo ( $post->info->comments_disabled == 0 ) ? 'checked' : ''; ?>> Allow comments</label></li>
     <li><label><input type="radio" name="comments_disabled" id="comments_disabled" value="1" <?php echo ( $post->info->comments_disabled == 1 ) ? 'checked' : ''; ?>> No comments</label></li>
    </ul>
    <input type="hidden" name="content_type" value="<?php echo $content_type; ?>">
    <p><input type="submit" name="submit" id="submit" value="Save!"></p>
   </div>
   <?php if ( $post->slug != '' ) { ?>
   <input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>">
   <?php } ?>
  </form>
 </div>
</div>
<?php include('footer.php'); ?>
