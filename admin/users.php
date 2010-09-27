<?php include('header.php');?>

<?php $theme->currentuser = User::identify(); ?>
<div class="container navigator">
	<span class="currentposition pct15 minor"><?php _e('no results'); ?></span>
	<span class="search pct50">
		<input id="search" type="search" placeholder="<?php _e('Type and wait to search'); ?>" value="<?php echo Utils::htmlspecialchars($search_args); ?>">
	</span>
	<div class="filters pct15">
		<ul class="dropbutton special_search">
			<?php foreach ( $special_searches as $text => $term ): ?>
			<li><a href="#<?php echo $term; ?>" title="<?php printf( _t('Filter results for \'%s\''), $text ); ?>"><?php echo $text; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>

<form method="post" action="">
<div class="container users">
	
	<div class="addnewuser item">

		<label for="new_username" class="incontent"><?php _e( 'Username' ); ?></label>
		<input type="text" name="new_username" id="new_username" value="<?php echo ( isset( $settings['new_username'] ) ) ? $settings['new_username'] : ''; ?>" class="border">

			<label for="new_email" class="incontent"><?php _e( 'E-Mail' ); ?></label>
			<input type="text" id="new_email" name="new_email" value="<?php echo ( isset( $settings['new_email'] ) ) ? $settings['new_email'] : ''; ?>" class="border">

			<label for="new_pass1" class="incontent"><?php _e( 'Password' ); ?></label>
			<input type="password" name="new_pass1" id="new_pass1" class="border">

			<label for="new_pass2" class="incontent"><?php _e( 'Password Again' ); ?></label>
			<input type="password" name="new_pass2" id="new_pass2" class="border">

		<input type="submit" name="newuser" value="<?php _e('Add User'); ?>">

	</div>

	<?php $theme->display('users_items'); ?>
</div>

<div class="container transparent">
	<div class="controls item">
		<span class="checkboxandselected pct25">
			<input type="checkbox" id="master_checkbox" name="master_checkbox">
			<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
		</span>

		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
	
		<span class="reassign minor">
			<?php  // printf( _t('Reassign posts to %s'), Utils::html_select('reassign', $authors )); ?> and
			<input type="submit" name="delete" value="<?php _e('Delete Selected'); ?>">
		</span>
	</div>
</div>
</form>






<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateUsers;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'users')) ?>";
itemManage.fetchReplace = $('.manage.users');
itemManage.inEdit = false;

</script>

<?php include('footer.php');?>
