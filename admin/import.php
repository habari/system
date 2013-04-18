<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>

<div class="container">
	<?php if ( isset( $theme->conflicting_plugins ) ) { ?>
		<h2><?php _e( 'Conflicting Plugins' ); ?></h2>
		<p class="error"><?php echo $theme->conflicting_plugins; ?></p>
	<?php } ?>
	
	<h2><?php echo _t('Import'); ?></h2>
	<?php echo $output; ?>
	
</div>
	
<?php include('footer.php'); ?>
