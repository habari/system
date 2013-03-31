<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include('header.php');?>

<div class="container navigation">
	<div class="pct40">

		<form>
		<select class="navigationdropdown" onChange="navigationDropdown.changePage(this.form.navigationdropdown)" name="navigationdropdown">
			<option value=""><?php _e('Complete User List'); ?></option>
		</select>
		</form>
	</div>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input id="search" type="search" placeholder="<?php _e('search users'); ?>" autofocus="autofocus">
	</span>
</div>

<form method="post" action="" autocomplete="off">
<div class="container users">
	
	<div class="addnewuser item">

		<?php echo $add_user_form; ?>

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
		<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">
	
		<span class="reassign minor">
			<?php printf( _t('Reassign posts to %s'), Utils::html_select('reassign', $authors )); ?> and
			<input type="submit" name="delete" value="<?php _e('Delete Selected'); ?>">
		</span>
	</div>
</div>
</form>






<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateUsers;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'users')) ?>";
itemManage.fetchReplace = $('.manage.users');

</script>

<?php include('footer.php');?>
