<?php $admin_title = _t( 'System Information' ); ?>

<?php include_once( 'header.php' ); ?>

<div class="container">
	<h2>SYSTEM INFORMATION</h2>

	<div class="manage">
	<?php foreach( $sysinfo as $key => $value ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $key; ?></span>
			<span class="message pct75 minor"><span><?php echo $value; ?></span></span>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2>SITE INFORMATION</h2>

	<div class="manage">
	<?php foreach( $siteinfo as $key => $value ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $key; ?></span>
			<span class="message pct75 minor"><span><?php echo $value; ?></span></span>
		</div>
	<?php endforeach; ?>
	</div>

</div>

<div class="container">
	<h2>USER CLASSES</h2>

	<div class="manage">
	<?php foreach( $classinfo as $fullpath ) : ?>
		<div class="item clear">
			<span class="pct100"><?php echo $fullpath; ?></span>
		</div>

	<?php endforeach; ?>
	<?php if ( empty( $fullpath ) ) : ?>
		<div class="item clear"><span class="pct100"><?php _e( "None found" ); ?></span></div>
	<?php endif; ?>
	</div>
</div>

<div class="container">
	<h2>PLUGIN INFORMATION</h2>

	<?php foreach($plugins as $section => $sec_plugins): ?>

	<h3><?php echo $section; ?></h3>
	<div class="manage">
	<?php foreach( $sec_plugins as $name => $pluginfile ) : ?>
		<div class="item clear">
			<span class="pct25"><?php echo $name; ?></span>
			<span class="message pct75 minor"><span><?php echo $pluginfile; ?></span></span>
		</div>

	<?php endforeach; ?>
	<?php if ( count($sec_plugins) == 0 ) : ?>
		<div class="item clear"><span class="pct100"><?php _e( "None found" ); ?></span></div>
	<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>


<?php include('footer.php'); ?>