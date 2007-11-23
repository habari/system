<link href="<?php Site::out_url( 'habari' ); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
body {
	text-align: center;
	min-width: 600px;
	background: #f1f1f1;
	font-family: Verdana;
}

.submit
{
	width: auto !important;
	position: relative;
}

</style>
	<div id="wrapper">
		<div id="masthead">
			<h1>Habari</h1>
		</div>
		<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">
		<div class="installstep ready done" id="databasesetup">
			<h2>Login to <?php Options::out( 'title' ); ?></h2>
			<div class="options">
				<div class="inputfield">
					<p>
						<label for="habari_username">Name:</label>
						<input type="text" size="25" name="habari_username" id="habari_username">
					</p>
					<p>
						<label for="habari_password">Password:</label>
						<input type="password" size="25" name="habari_password" id="habari_password">
					</p>
					<p>
						<input class="submit" type="submit" value="GO!">
					</p>
				</div>
			</div>			
			<div class="bottom"></div>
		</div>
	</div>
</div>
