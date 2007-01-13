<div id="content-area">
	<div class="dashbox c3" id="welcome">
		<h1>Import your old blog content</h1>
		<p>With this form you can migrate all your post, page, comment and user content to Habari.</p>
		<form method="post">
			<dl>
				<dt>Connection String<dt>
				<dd><input type="text" name="connection" value="mysql:host=localhost;dbname=asymptomatic" /></dd>
				<dt>Username</dt>
				<dd><input type="text" name="username" value="root" /></dd>
				<dt>Password</dt>
				<dd><input type="password" name="password" value="" /></dd>
				<dt>Prefix</dt>
				<dd><input type="text" name="prefix" value="wp_" /></dd>
			</dl>
			<p class="submit"><input type="submit" name="import" value="Import" /></p>
		</form>

	</div>
</div>
<?php 
// unset the $db_connection variable, since we don't need it any more
unset( $db_connection );

?>
