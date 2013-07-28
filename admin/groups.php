<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include('header.php'); ?>
<div class="container navigation">
	<span class="columns seven">
		<select name="navigationdropdown" onchange="navigationDropdown.changePage();" tabindex="1">
			<option value="all"><?php _e('All Groups'); ?></option>
			<?php foreach($groups as $group): ?>
				<option value="<?php echo URL::get('display_group', 'id=' . $group->id); ?>"><?php echo $group->name; ?></option>
			<?php endforeach; ?>
		</select>
	</span>
	<span class="columns one or">
		<?php _e('or'); ?>
	</span>
	<span class="columns eight">
		<input type="search" id="search" placeholder="<?php _e('search settings'); ?>" tabindex="2" autofocus="autofocus">
	</span>
</div>

<form method="post" action="" id="groupform">
<div class="container main groups allgroups">
	<h2 class="lead"><?php _e('Group Management'); ?></h2>
	<div id="groups">
		<?php foreach ( $groups as $group ):
			$users = array();
			foreach ( $group->users as $user ) {
				if ( $user->id == 0 ) {
					$users[] = '<strong>' . $user->displayname . '</strong>';
				}
				elseif ( $user->username == User::identify()->username ) {
					$users[] = '<strong><a href="' . URL::get( 'own_user_profile' ) . '">' . $user->displayname . '</a></strong>';
				}
				else {
					$users[] = '<strong><a href="' . Url::get( 'user_profile', array( 'user' => $user->username ) ) . '">' . $user->displayname . '</a></strong>';
				}
			}
			include('groups_item.php');
		endforeach; ?>
	</div>
</div>

<div class="container main addgroup">
	<h2 class="lead"><?php _e('Add Group'); ?></h2>

	<?php echo $add_group_form->get(); ?>

</div>
</form>

<script type="text/javascript">
	itemManage.updateURL = habari.url.ajaxUpdateGroups;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'groups')) ?>";
	itemManage.fetchReplace = $('#groups');
</script>

<?php include('footer.php');?>
