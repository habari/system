<?php include('header.php');?>

<?php $currentuser = User::identify(); ?>


<div class="container navigation">
	<span class="pct40">

		<form>
		<select class="navigationdropdown" onChange="navigationDropdown.changePage(this.form.navigationdropdown)" name="navigationdropdown">
			<?php /*
			foreach ( Users::get_all() as $user ) {
				if ( $user->username == $currentuser->username ) {
					$url = Url::get( 'admin', 'page=user' );
				}
				else {
					$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
				}
				echo '<option id="' . $user->id . '" value="' . $url . '">' . $user->displayname . '</option>';
			} */ ?>
			<option value=""><?php _e('Complete User List'); ?></option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" placeholder="<?php _e('search users'); ?>" autosave="habarisettings" results="10">
	</span>
</div>


<div class="container users">

	<!-- a b c d e f g h i j k l m n o p q r s t u v x y z æ å ø -->

	<ul>
		<?php foreach (Users::get_all() as $user) {
			if ( $user->username == $currentuser->username )
				$url = Url::get( 'admin', 'page=user' );
			else
				$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
		?>

		<li class="item clear">
			<span class="user pct100"><a href="<?php echo $url ?>" title="<?php _e('Open '. $user->displayname .'\'s user page') ?>"><?php echo $user->displayname ?></a></span><br>

			<span class="aka pct 100">
				<strong><?php echo $user->username ?></strong> 
				<?php _e('was last seen'); ?> <strong><?php echo date('M j, Y', strtotime($user->info->authenticate_time)) ?> at <?php echo date('H:i', strtotime($user->info->authenticate_time)) ?></strong>
				<?php _e('and currently has') ?> <strong><?php echo Posts::count_by_author( $user->id, Post::status('published') ) . _t(' published posts, ') . Posts::count_by_author( $user->id, Post::status('draft') ) . _t(' pending drafts and ') . Posts::count_by_author( $user->id, Post::status('private') ) . _t(' private posts'); ?></strong>
			</span>
		</li>

		<?php } ?>
	</ul>

</div>


<div class="container users addnewuser settings">

	<h2><?php _e('Add New User'); ?></h2>

	<form method="post" action="">

	<span class="pct25">
		<label for="username" class="incontent">Username</label>
		<input type="text" size="40" name="username" id="username" value="<?php echo ( isset( $settings['username'] ) ) ? $settings['username'] : ''; ?>" class="styledformelement">
	</span>

	<span class="pct25">
		<label for="email" class="incontent">E-Mail</label>
		<input type="text" size="40" id="email" name="email" value="<?php echo ( isset( $settings['email'] ) ) ? $settings['email'] : ''; ?>" class="styledformelement">
	</span>
	<span class="pct25">
		<label for="pass1" class="incontent">Password</label>
		<input type="password" size="40" name="pass1" id="pass1" class="styledformelement">
	</span>
	<span class="pct25 last-child">
		<label for="pass2" class="incontent">Password Again</label>
		<input type="password" size="40" name="pass2" id="pass2" class="styledformelement">
	</span>

	<input type="hidden" name="action" value="newuser">
	<input type="submit" value="<?php _e('Add User'); ?>">
	</form>

</div>

<?php include('footer.php');?>
