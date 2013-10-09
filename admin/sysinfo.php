<?php namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
$admin_title = _t( 'System Information' );

include_once( 'header.php' ); ?>

<div class="container main">
	<h2 class="lead"><?php _e( "System Information" ); ?></h2>
	<?php $plaintext_output = strtoupper( _t( "System Information" ) ) . "\n"; ?>

	<table>
	<?php foreach ( $sysinfo as $key => $value ) : ?>
		<tr>
			<td><?php echo $key;
				$plaintext_output .= $key; ?></td>
			<td><?php echo $value;
				$plaintext_output .= ": $value\n"; ?></td>
		</tr>
	<?php endforeach; ?>
	</table>

</div>

<div class="container main">
	<h2 class="lead"><?php _e( "Site Information" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "Site Information" ) ) . "\n"; ?>

	<table class="container">
	<?php foreach ( $siteinfo as $key => $value ) : ?>
		<tr>
			<td><?php echo $key;
				$plaintext_output .= $key; ?></td>
			<td><?php echo $value;
				$plaintext_output .= ": $value\n"; ?></td>
		</tr>
	<?php endforeach; ?>
	</table>

</div>

<div class="container main">
	<h2 class="lead"><?php _e( "Plugin Information" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "Plugin Information" ) ); ?>
	<table>
	<?php foreach ( $plugins as $section => $sec_plugins ): ?>

	<tr><td colspan="2"><h3 class="sub"><?php echo $section;
		$plaintext_output .= "\n/$section/plugins:\n"; ?></h3></td></tr>

	<?php foreach ( $sec_plugins as $name => $plugindata ) : ?>
		<tr>
			<td><?php echo $name . ' ' . $plugindata['version'] ;
				$plaintext_output .= $name . ' ' . $plugindata['version']; ?></td>
			<td><?php echo $plugindata['file'];
				$plaintext_output .= ": " . $plugindata['file'] . "\n"; ?></td>
		</tr>

	<?php endforeach; ?>
	<?php if ( count($sec_plugins) == 0 ) : ?>
		<tr><td><?php _e( "None found" );
			$plaintext_output .= _t( "None found" ) . "\n"; ?></td></tr>
	<?php endif; ?>
	<?php endforeach; ?>
	</table>
</div>

<div class="container main">
	<h2 class="lead"><?php _e( "User Classes" ); ?></h2>
	<?php $plaintext_output .= "\n" . strtoupper( _t( "User Classes" ) ) . "\n"; ?>

	<table>
	<?php foreach ( $classinfo as $fullpath ) : ?>
		<tr><td>
			<?php echo $fullpath;
				$plaintext_output .= "$fullpath\n"; ?>
		</td></tr>

	<?php endforeach; ?>
	<?php if ( empty( $fullpath ) ) : ?>
		<tr><td><?php _e( "None found" );
			$plaintext_output .= _t( "None found" ) . "\n"; ?></td></tr>
	<?php endif; ?>
	</table>
</div>

<div class="container main">
	<h2 class="lead"><?php _e( "All Results" ); ?></h2>
	<textarea class="full-width" rows = "<?php echo substr_count( $plaintext_output, "\n" ); ?>"><?php echo $plaintext_output; ?></textarea>
</div>

<?php include('footer.php'); ?>
