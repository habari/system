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
	<?php foreach( glob( HABARI_PATH . "/user/classes/*.php") as $fullpath ) : ?>
		<div class="item clear">
			<span class="pct100"><?php echo implode( split( HABARI_PATH . "/user/classes/", $fullpath ) ); ?></span>
		</div>

	<?php endforeach; ?>
	<?php if ( empty( $fullpath ) ) : ?>
		<div class="item clear"><span class="pct100"><?php _e( "None found" ); ?></span></div>
	<?php endif; ?>
	</div>
</div>

<?php include('footer.php'); ?>
