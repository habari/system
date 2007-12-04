<!-- commentsform -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
?>
     <div class="comments">
      <h4 id="respond" class="reply">Leave a Reply</h4>
<?php
$class= '';
$cookie= 'comment_' . Options::get( 'GUID' );
$commenter_name= '';
$commenter_email= '';
$commenter_url= '';
if ( $user ) {
	$commenter_name= $user->username;
	$commenter_email= $user->email;
	$commenter_url= Site::get_url('habari');
}
elseif ( isset( $_COOKIE[$cookie] ) ) {
	list( $commenter_name, $commenter_email, $commenter_url )= explode( '#', $_COOKIE[$cookie] );
}
if ( isset( $_SESSION['comment'] ) ) {
	$details= Session::get_set('comment');
	$commenter_name= $details['name'];
	$commenter_email= $details['email'];
	$commenter_url= $details['url'];
}

if ( Session::has_errors() ) {
	Session::messages_out();
}
?>
      <form action="<?php URL::out( 'submit_feedback', array( 'id' => $post->id ) ); ?>" method="post" id="commentform">
       <div id="comment-personaldetails">
        <p>
         <input type="text" name="name" id="name" value="<?php echo $commenter_name; ?>" size="22" tabindex="1">
         <label for="name"><small><strong>Name</strong></small></label>
        </p>
        <p>
         <input type="text" name="email" id="email" value="<?php echo $commenter_email; ?>" size="22" tabindex="2">
         <label for="email"><small><strong>Mail</strong> (will not be published)</small></label>
        </p>
        <p>
         <input type="text" name="url" id="url" value="<?php echo $commenter_url; ?>" size="22" tabindex="3">
         <label for="url"><small><strong>Website</strong></small></label>
        </p>
       </div>
       <p>
<textarea name="content" id="content" cols="100" rows="10" tabindex="4">
<?php if ( isset( $details['content'] ) ) { echo $details['content']; } ?>
</textarea>
       </p>
       <p>
        <input name="submit" type="submit" id="submit" tabindex="5" value="Submit">
       </p>
       <div class="clear"></div>
      </form>
     </div>
<!-- /commentsform -->
