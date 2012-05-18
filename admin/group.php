<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>
<div class="container navigation">
	<span class="pct40">
		<select name="navigationdropdown" onchange="navigationDropdown.changePage();" tabindex="1">
			<option value="<?php echo URL::get('admin', 'page=groups'); ?>"><?php _e('All Groups'); ?></option>
			<?php foreach ( $groups as $group_nav ): ?>
				<option value="<?php echo URL::get('admin', 'page=group&id=' . $group_nav->id); ?>"<?php if ($group_nav->id == $id): ?> selected="selected"<?php endif; ?>><?php echo $group_nav->name; ?></option>
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

<div class="container transparent groupstats">
<p><?php echo sprintf( _n( 'Group %1$s has <strong>%2$d</strong> member', 'Group %1$s has <strong>%2$d</strong> members', count( $members ) ), "<strong>$group->name</strong>", count( $members ) ); ?></p>
</div>

<form name="update-group" id="update-group" action="<?php URL::out('admin', 'page=group'); ?>" method="post">
<div class="container settings group groupmembers" id="groupmembers">

	<h2><?php _e('Group Members'); ?></h2>
	
	<div class="item clear" id="assignedusers">
		<span class="pct100" id="currentusers">
			<span class="pct20">
				<label><strong><?php _e('Members'); ?></strong></label>
			</span>
			<span class="pct80 memberlist"></span>
		</span>
		<span class="pct100" id="newusers">
			<span class="pct20">
				<label><strong><?php _e('Members To Add'); ?></strong></label>
			</span>
			<span class="pct80 memberlist"></span>
		</span>
	</div>
	<div class="item clear">
		<span class="pct100">
			<span class="pct20">&nbsp;</span>
			<span class="pct80" id="add_users" >
				<span class="pct40"><select name="assign_user" id="assign_user"></select></span>
				<span class="pct60"><input type="button" id="add_user" value="<?php _e('Add'); ?>" class="button add"></span>
			</span>
		</span>
		<span class="pct100" id="removedusers">
			<span class="pct20">
				<label><strong><?php _e('Members To Remove'); ?></strong></label>
			</span>
			<span class="pct80 memberlist"></span>
		</span>
	</div>
		<?php foreach ( $users as $user ): ?>
			<input type="hidden" name="user[<?php echo $user->id; ?>]" value="<?php echo ($user->membership) ? '1' : 0; ?>" id="user_<?php echo $user->id; ?>">
		<?php endforeach; ?>
	
</div>

<div class="container settings group groupacl" id="groupacl">

	<h2><?php _e('Group Permissions'); ?></h2>

	<?php
	foreach ( $grouped_tokens as $group_name => $token_group ):
		$crud_tokens = ( isset($token_group['crud']) ) ? $token_group['crud'] : array();
		$bool_tokens = ( isset($token_group['bool']) ) ? $token_group['bool'] : array();
	?>
	<div class="item clear permission-group">
		<h3><?php echo $group_name; ?></h3>
		<?php if ( !empty( $crud_tokens ) ): ?>
			<table id="<?php echo $group_name; ?>-crud-permissions" class="pct100 crud-permissions">
				<tr class="head">
					<th class="pct40"><?php _e( 'Token Description' ); ?></th>
					<?php foreach ( $access_names as $name ): ?>
					<th class="pct10"><?php _e( $name ); ?></th>
					<?php endforeach; ?>
				</tr>
				<?php foreach ( $crud_tokens as $token ): ?>
				<tr>
					<td class="token_description pct40"><strong><?php echo $token->description; ?></strong></td>
					<?php 
					foreach ( $access_names as $name ):
						$checked = ( isset($token->access) && ACL::access_check( $token->access, $name ) ) ? ' checked' : '';
					?>
						<td class="token_access pct10">
							<input type="checkbox" id="token_<?php echo $token->id . '_' . $name; ?>" class="bitflag-<?php echo $name; ?>" name="tokens[<?php echo $token->id . '][' . $name; ?>]" <?php echo $checked; ?>>
						</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
		<?php if ( !empty( $bool_tokens ) ): ?>
			<table id="<?php echo $group_name; ?>-bool-permissions" class="pct100 bool-permissions">
				<tr class="head">
					<th class="pct40"><?php _e( 'Token Description' ); ?></th>
					<th class="pct10"><?php _e( 'allow' ); ?></th>
					<th class="pct10"><?php _e( 'deny' ); ?></th>
				</tr>
				<?php foreach ( $bool_tokens as $token ): ?>
				<tr>
					<td class="token_description pct40"><strong><?php echo $token->description; ?></strong></td>
					<?php $checked = ( isset($token->access) && ACL::access_check( $token->access, 'any' ) ) ? ' checked' : '';?>
					<td class="token_access pct10">
						<input type="checkbox" id="token_<?php echo $token->id . '_full'; ?>" class="bitflag-full" name="tokens[<?php echo $token->id; ?>][full]" <?php echo $checked; ?>>
					</td>
					<?php $checked = ( isset($token->access) && ACL::access_check( $token->access, 'deny' ) ) ? ' checked' : '';?>
					<td class="token_access pct10">
						<input type="checkbox" id="token_<?php echo $token->id . '_deny'; ?>" class="bitflag-deny" name="tokens[<?php echo $token->id; ?>][deny]" <?php echo $checked; ?>>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>

</div>

<div class="container controls transparent">
	<span class="pct50">
		<input type="submit" name="delete" value="<?php _e('Delete'); ?>" class="delete button">
	</span>
	<span class="pct50">
		<input type="submit" value="<?php _e('Apply'); ?>" class="button save">
	</span>

	<input type="hidden" name="id" id="id" value="<?php echo $group->id; ?>">

	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">

</div>
</form>

<?php include('footer.php');?>
