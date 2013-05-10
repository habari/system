<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $admin_title = _t( 'System Information' ); ?>

<?php include_once( 'header.php' ); ?>

<div class="container">
	<h2><?php _e( "System Information" ); ?></h2>
	<?php $plaintext_output = strtoupper( _t( "System Information" ) ) . "\n"; ?>

	<div class="manage">
	<?php foreach ( $sysinfo as $key => $value ) : ?>
		<div class="item">
			<?php echo $key; 
				$plaintext_output .= $key; ?> 
			<?php echo $value;
				$plaintext_output .= ": $value\n"; ?>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2><?php _e( "Site Information" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "Site Information" ) ) . "\n"; ?>

	<div class="manage">
	<?php foreach ( $siteinfo as $key => $value ) : ?>
		<div class="item">
			<?php echo $key; 
				$plaintext_output .= $key; ?>
			<?php echo $value;
				$plaintext_output .= ": $value\n"; ?>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2><?php _e( "User Classes" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "User Classes" ) ) . "\n"; ?>

	<div class="manage">
	<?php foreach ( $classinfo as $fullpath ) : ?>
		<div class="item">
			<?php echo $fullpath; 
				$plaintext_output .= "$fullpath\n"; ?>
		</div>

	<?php endforeach; ?>
	<?php if ( empty( $fullpath ) ) : ?>
		<div class="item"><?php _e( "None found" ); 
			$plaintext_output .= _t( "None found" ) . "\n"; ?></div>
	<?php endif; ?>
	</div>
</div>

<div class="container">
	<h2><?php _e( "Plugin Information" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "Plugin Information" ) ); ?>

	<?php foreach ( $plugins as $section => $sec_plugins ): ?>

	<h3><?php echo $section; 
		$plaintext_output .= "\n/$section/plugins:\n"; ?></h3>
	<div class="manage">
	<?php foreach ( $sec_plugins as $name => $pluginfile ) : ?>
		<div class="item">
			<?php echo $name; 
				$plaintext_output .= $name; ?>
			<?php echo $pluginfile;
				$plaintext_output .= ": $pluginfile\n"; ?>
		</div>

	<?php endforeach; ?>
	<?php if ( count($sec_plugins) == 0 ) : ?>
		<div class="item"><?php _e( "None found" ); 
			$plaintext_output .= _t( "None found" ) . "\n"; ?></div>
	<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>

<div class="container">
	<h2><?php _e( "All Results" ); ?></h2>
	<textarea class="full-width" rows = "<?php echo substr_count( $plaintext_output, "\n" ); ?>"><?php echo $plaintext_output; ?></textarea>
</div>

<?php include('footer.php'); ?>
