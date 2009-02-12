<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title><?php printf( _t('Login to %s'), Options::get( 'title' ) ); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<link rel="stylesheet" href="<?php Site::out_url('admin_theme'); ?>/css/admin.css" type="text/css" media="screen">

	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.core.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.tabs.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.sortable.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('habari'); ?>/3rdparty/humanmsg/humanmsg.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('habari'); ?>/3rdparty/hotkeys/jquery.hotkeys.js" type="text/javascript"></script>

	<script type="text/javascript">
	var habari = {
		url: { habari: '<?php Site::out_url('habari'); ?>' }
	};
	</script>
	<script src="<?php Site::out_url('admin_theme'); ?>/js/admin.js" type="text/javascript"></script>

	<?php
		Plugins::act( 'admin_header', $this );
		Stack::out( 'admin_header_javascript', array('Stack', 'scripts') );
		Stack::out( 'admin_stylesheet', array('Stack', 'styles') );
	?>

</head>
<body class="login">

	<div id="page">

		<h1><a href="<?php Site::out_url('habari'); ?>" title="<?php _e('Go to Site'); ?>"><?php Options::out('title'); ?></a></h1>

		<div class="container">
			<?php Plugins::act( 'theme_loginform_before' ); ?>
				<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">

					<p>
						<label for="habari_username" class="incontent abovecontent"><?php _e('Name'); ?></label><input type="text" name="habari_username" id="habari_username"<?php if(isset( $habari_username )) { ?> value="<?php echo htmlspecialchars( $habari_username ); ?>"<?php } ?> placeholder="<?php _e('name'); ?>" class="styledformelement">
					</p>
					<p>
						<label for="habari_password" class="incontent abovecontent"><?php _e('Password'); ?></label><input type="password" name="habari_password" id="habari_password" placeholder="<?php _e('password'); ?>" class="styledformelement">
					</p>
					<?php Plugins::act( 'theme_loginform_controls' ); ?>
					<p>
						<input class="submit" type="submit" value="<?php _e('Login'); ?>">
					</p>
					<p id="password_utils">
						<a href="#" onclick="if($('#habari_username').val() ==''){alert('Please fill in the name field, and click again.');}else{window.location='<?php URL::out('user', array('page'=>'password_request')); ?>?username=' +$('#habari_username').val();}"><?php _e('Forgot your password?'); ?></a>
					</p>

				</form>
     			<?php Plugins::act( 'theme_loginform_after' ); ?>
		</div>

	</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
?>
	<script type="text/javascript">
	var password_label;
	$(document).ready( function() {
		<?php Session::messages_out( true, array( 'Format', 'humane_messages' ) ); ?>
		password_label = $('label[for=habari_password]');
		// to fix autofill issues, we need to check the password field on every keyup
		$('#habari_username').keyup( function() {
			setTimeout( "labeler.check( password_label );", 10 );
		} ).click( function() {
			setTimeout( "labeler.check( password_label );", 50 );
		} );
		// for autofill without user input
		setTimeout( function(){ labeler.check( password_label ); }, 10 );
	})
  </script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>