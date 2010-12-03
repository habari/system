<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>

<div id="search">
	<?php $theme->search_form() ?>
</div>
<div id="feeds">
	<div class="feedlink"><a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>"><?php _e( "{blog entries}" ); ?></a></div>
	<div class="feedlink"><a href="<?php URL::out( 'atom_feed_comments' ); ?>"><?php _e( "{comments}" ); ?></a></div>
</div>
<div id="habari-link">
<?php if ($show_powered) : ?>
	<a href="http://habariproject.org/" title="<?php _e( "Powered by Habari" ); ?>"><img src="<?php Site::out_url('theme'); ?>/images/pwrd_habari.png" alt="<?php _e( "Powered by Habari" ); ?>"></a>
<?php  endif; ?>
</div>
<div id="sidebar">
<!-- Call your plugins theme functions here-->
<?php $theme->area('sidebar'); ?>
</div>
