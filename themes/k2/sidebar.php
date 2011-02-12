<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!-- sidebar -->
<?php Plugins::act( 'theme_sidebar_top' ); ?>

		<div id="search">
		<h2><?php _e('Search'); ?></h2>
<?php $theme->display ('searchform' ); ?>
		</div>

		<div class="sb-about">
		<h2><?php _e('About'); ?></h2>
		<p><?php Options::out('about'); ?></p>
		</div>

<?php $theme->area( 'sidebar' ); ?>

		<div class="sb-user">
		<h2><?php _e('User'); ?></h2>
<?php $theme->display ( 'loginform' ); ?>
		</div>

<?php Plugins::act( 'theme_sidebar_bottom' ); ?>
<!-- /sidebar -->
