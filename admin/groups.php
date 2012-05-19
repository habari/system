<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>
<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.changePage();" tabindex="1">
			<option value="all"><?php _e('All Groups'); ?></option>
			<?php foreach($groups as $group): ?>
				<option value="<?php echo URL::get('admin', 'page=group&id=' . $group->id); ?>"><?php echo $group->name; ?></option>
			<?php endforeach; ?>
		</select>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" id="search" placeholder="<?php _e('search settings'); ?>" tabindex="2" autofocus="autofocus">
	</span>
</div>

<form method="post" action="" id="groupform">
<div class="container groups allgroups">
	<h2><?php _e('Group Management'); ?></h2>
	<div id="groups">
		<?php foreach ( $groups as $group ):
			$users = array();
			foreach ( $group->users as $user ) {
				if ( $user->id == 0 ) {
					$users[] = '<strong>' . $user->displayname . '</strong>';
				}
				elseif ( $user->username == User::identify()->username ) {
					$users[] = '<strong><a href="' . URL::get( 'admin', 'page=user' ) . '">' . $user->displayname . '</a></strong>';
				}
				else {
					$users[] = '<strong><a href="' . Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) ) . '">' . $user->displayname . '</a></strong>';
				}
			}
			include('groups_item.php');
		endforeach; ?>
	</div>
</div>

<div class="container addgroup">
	<h2><?php _e('Add Group'); ?></h2>
	
	<div class="item clear">
		<span class="pct25">
			<label for="new_groupname"><?php _e( 'Group Name' ); ?></label>
		</span>
		<span class="pct25">
			<input type="text" name="new_groupname" id="new_groupname" value="<?php echo ( isset( $addform['name'] ) ) ? $addform['name'] : ''; ?>" class="border">
		</span>
	</div>
	
	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">
	
	<div class="item submit clear">
		<span class="pct25">
			<input type="submit" name="newgroup" value="<?php _e('Add Group'); ?>">
		</span>
	</div>

	
</div>
</form>

<script type="text/javascript">
	itemManage.updateURL = habari.url.ajaxUpdateGroups;
	itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'groups')) ?>";
	itemManage.fetchReplace = $('#groups');
</script>

<?php include('footer.php');?>
