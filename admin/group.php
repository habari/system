<?php include('header.php'); ?>
<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.changePage();" tabindex="1">
			<option value="<?php echo URL::get('admin', 'page=groups'); ?>"><?php _e('All Groups'); ?></option>
			<?php foreach($groups as $group_nav): ?>
				<option value="<?php echo URL::get('admin', 'page=group&id=' . $group_nav->id); ?>"<?php if($group_nav->id == $id): ?> selected="selected"<?php endif; ?>><?php echo $group_nav->name; ?></option>
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

<div class="container transparent groupstats">
<p><strong><?php echo $group->name; ?></strong> gives <strong>0</strong> permissions to <strong><?php echo count($members); ?></strong> users</p>
</div>

<form name="update-group" id="update-group" action="<?php URL::out('admin', 'page=group'); ?>" method="post">
<div class="container settings group groupmembers" id="groupmembers">

	<h2><?php _e('Group Members'); ?></h2>
	
	<div class="item clear assignedusers">
		<span class="pct20">
			<label><?php _e('Assigned Users'); ?></label>
		</span>
		<span class="pct80">
			<span class="pct100" id="currentusers"><?php foreach($users as $user): ?><a class="user id-<?php echo $user->id; ?>"<?php if(!$user->membership): ?> style="display:none;"<?php endif; ?> href="#remove" title="Remove member"><span class="id"><?php echo $user->id; ?></span><span class="name"><?php echo $user->displayname; ?></span></a><?php endforeach; ?></span>
			<span class="pct100" id="addusers"<?php if(count($potentials) < 1): ?> style="display:none;"<?php endif; ?>>
				<span class="pct40"><?php echo Utils::html_select('assign_user', $potentials); ?></span>
				<span class="pct60"><input type="button" value="<?php _e('Add'); ?>" class="button add"></span>
			</span>
			<?php foreach($users as $user): ?>
				<input type="hidden" name="user[<?php echo $user->id; ?>]" value="<?php if($user->membership): ?>1<?php else: ?>0<?php endif; ?>" id="user_<?php echo $user->id; ?>">
			<?php endforeach; ?>
		</span>
	</div>
	
</div>

<div class="container settings group grouppermissions" id="grouppermissions">

	<h2><?php _e('Group Permissions'); ?></h2>
	
	<div class="item clear grouppermissions">
		<span class="pct20">
			<label><?php _e('Available Permissions'); ?></label>
		</span>
		<span class="pct80">
			<ul id="permissions">
			<?php foreach($permissions as $permission): ?>
				<li class="permission"><?php echo Utils::html_select('permission_' . $permission->id, $access_levels, $permission->access); ?><?php echo $permission->name; ?></li>
			<?php endforeach; ?>
			</ul>
		</span>
	</div>
	
</div>

<div class="container controls transparent">
	<span class="pct50">
		<input type="submit" value="<?php _e('Apply'); ?>" class="button save">
	</span>
	<span class="pct50">
		<input type="submit" name="delete" value="<?php _e('Delete'); ?>" class="delete button">
	</span>

	<input type="hidden" name="id" id="id" value="<?php echo $group->id; ?>">

	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

</div>
</form>

<?php include('footer.php');?>
