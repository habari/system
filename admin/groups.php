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
		<input type="search" id="search" placeholder="<?php _e('search settings'); ?>" autosave="habarisettings" results="10" tabindex="2">
	</span>
</div>

<form method="post" action="" id="groupform">
<div class="container groups allgroups">
	<h2><?php _e('Group Management'); ?></h2>
	<div id="groups">
		<?php foreach($groups as $group):
			$group= UserGroup::get_by_id($group->id);
			$users= array();
			foreach($group->members as $id) {
				$user= User::get_by_id($id);
				$users[]= '<strong><a href="' . URL::get('admin', 'page=user&id=' . $user->id) . '">' . $user->displayname . '</a></strong>';
			}
			include('groups_item.php');
		endforeach; ?>
	</div>
</div>

<div class="container addgroup">
	<h2><?php _e('Add Group'); ?></h2>
	
	<div class="item clear">
		<span class="pct25">
			<label for="new_groupname">Group Name</label>
		</span>
		<span class="pct25">
			<input type="text" name="new_groupname" id="new_groupname" value="<?php echo ( isset( $addform['name'] ) ) ? $addform['name'] : ''; ?>" class="border">
		</span>
	</div>
	
	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
	
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
	itemManage.inEdit = false;
</script>

<?php include('footer.php');?>
