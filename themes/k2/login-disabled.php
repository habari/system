<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $theme->display( 'header'); ?>
<!-- login -->
	<div class="content">
	<div id="primary">
		<div id="primarycontent" class="hfeed">
<?php $theme->display( 'loginform'); ?>
<?php Plugins::act( 'theme_login' ); ?>
		</div>

	</div>

	<hr>

	<div class="secondary">

<?php $theme->display( 'sidebar'); ?>

	</div>

	<div class="clear"></div>
	</div>
<!-- /login -->
<?php $theme->display( 'footer'); ?>
