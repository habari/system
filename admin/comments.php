<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>

<?php include( 'navigator.php' ); ?>

<form method="post" name="moderation" action="<?php URL::out( 'admin', array( 'page' => 'comments', 'status' => $status ) ); ?>">
	<input type="hidden" name="search" value="<?php echo Utils::htmlspecialchars($search); ?>">
	<input type="hidden" name="status" value="<?php echo Utils::htmlspecialchars($status); ?>">
	<input type="hidden" id="nonce" name="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" id="timestamp" name="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" id="password_digest" name="password_digest" value="<?php echo $wsse['digest']; ?>">

<div class="container transparent item comments controls">
	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox" name="master_checkbox" class="select_all">
		<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
	</span>
	<ul class="comments-action dropbutton alert">
		<li class=""><a href="*" name="do_delete" title="<?php _e( 'Delete selected comments' ); ?>" onclick="itemManage.update( 'delete' ); return false;"><?php _e( 'Delete Selected' ); ?></a></li>
		<li class=""><a href="*" name="do_delete_spam" title="<?php _e( 'Delete all spam comments' ); ?>" onclick="itemManage.update( 'delete_spam' ); return false;"><?php _e( 'Delete All Spam' ); ?></a></li>
		<li class=""><a href="*" name="do_delete_unapproved" title="<?php _e( 'Delete all unapproved comments' ); ?>" onclick="itemManage.update( 'delete_unapproved' ); return false;"><?php _e( 'Delete All Unapproved' ); ?></a></li>
	</ul>
	<ul class="comments-action dropbutton">
		<li class=""><a href="*" name="do_approve" title="<?php _e( 'Approve selected comments' ); ?>" onclick="itemManage.update( 'approve' ); return false;"><?php _e( 'Mark selected approved' ); ?></a></li>
		<li class=""><a href="*" name="do_unapprove" title="<?php _e( 'Unapprove selected comments' ); ?>" onclick="itemManage.update( 'unapprove' ); return false;"><?php _e( 'Mark selected unapproved' ); ?></a></li>
		<li class=""><a href="*" name="do_spam" title="<?php _e( 'Mark selected comments as spam' ); ?>" onclick="itemManage.update( 'spam' ); return false;"><?php _e( 'Mark selected spam' ); ?></a></li>
	</ul>
</div>

<div id="comments" class="container manage comments">

<?php $theme->display('comments_items'); ?>

</div>


<div class="container transparent item comments controls">
	<span class="checkboxandselected pct30">
		<input type="checkbox" id="master_checkbox_2" name="master_checkbox_2" class="select_all">
		<label class="selectedtext minor none" for="master_checkbox_2"><?php _e('None selected'); ?></label>
	</span>
	<ul class="comments-action dropbutton alert">
		<li class=""><a href="*" name="do_delete" title="<?php _e( 'Delete selected comments' ); ?>" onclick="itemManage.update( 'delete' ); return false;"><?php _e( 'Delete Selected' ); ?></a></li>
		<li class=""><a href="*" name="do_delete_spam" title="<?php _e( 'Delete all spam comments' ); ?>" onclick="itemManage.update( 'delete_spam' ); return false;"><?php _e( 'Delete All Spam' ); ?></a></li>
		<li class=""><a href="*" name="do_delete_unapproved" title="<?php _e( 'Delete all unapproved comments' ); ?>" onclick="itemManage.update( 'delete_unapproved' ); return false;"><?php _e( 'Delete All Unapproved' ); ?></a></li>
	</ul>
	<ul class="comments-action dropbutton">
		<li class=""><a href="*" name="do_approve" title="<?php _e( 'Approve selected comments' ); ?>" onclick="itemManage.update( 'approve' ); return false;"><?php _e( 'Mark selected approved' ); ?></a></li>
		<li class=""><a href="*" name="do_unapprove" title="<?php _e( 'Unapprove selected comments' ); ?>" onclick="itemManage.update( 'unapprove' ); return false;"><?php _e( 'Mark selected unapproved' ); ?></a></li>
		<li class=""><a href="*" name="do_spam" title="<?php _e( 'Mark selected comments as spam' ); ?>" onclick="itemManage.update( 'spam' ); return false;"><?php _e( 'Mark selected spam' ); ?></a></li>
	</ul>
</div>

</form>

<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateComment;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'comments')) ?>";
itemManage.fetchReplace = $('#comments');

</script>


<?php include('footer.php'); ?>
