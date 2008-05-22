<?php include('header.php');?>
<div class="container">
<hr>
<div class="column prepend-1 span-22 append-1">
<?php
$currentuser = User::identify();
?>
<h3><?php _e('User Management'); ?></h3>
<p><?php _e('Add, edit and remove users from your site from this interface.'); ?></p>
<h3><strong><?php _e('Users'); ?></strong></h3>
<ul>
<?php
foreach ( Users::get_all() as $user )
{
	if ( $user->username == $currentuser->username ) {
		$url = Url::get( 'admin', 'page=user' );
	}
	else {
		$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
	}
	echo '<li>';
	echo '<a href="' . $url . '">' . $user->username . '</a> <em>(' . _t('Last login:') . $user->info->authenticate_time . ')</em><br>';
	echo Posts::count_by_author( $user->id, Post::status('published') ) . _t(' published posts, ') . Posts::count_by_author( $user->id, Post::status('draft') ) . _t(' pending drafts, and ') . Posts::count_by_author( $user->id, Post::status('private') ) . _t(' private posts.');
	echo '</li>';
}
?>
</ul>

<form method="post" action="">
<h3><strong><?php _e('Add a new user'); ?></strong></h3>
<p><?php _e('Username:'); ?> <input type="text" size="40" name="username" value="<?php echo ( isset( $settings['username'] ) ) ? $settings['username'] : ''; ?>"></p>
<p><?php _e('Email:'); ?> <input type="text" size="40" name="email" value="<?php echo ( isset( $settings['email'] ) ) ? $settings['email'] : ''; ?>"></p>
<p><?php _e('Password (twice to confirm):'); ?></p>
<p><input type="password" size="40" name="pass1"></p>
<p><input type="password" size="40" name="pass2"></p>
<p><input type="hidden" name="action" value="newuser"></p>
<p><input type="submit" value="<?php _e('Add User'); ?>"></p>
</form>
</div>
</div>
<?php include('footer.php');?>
