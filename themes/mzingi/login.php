<?php $theme->display ( 'header' ); ?>
<!--begin content-->
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent">
			<?php include 'loginform.php'; ?>
			</div>
		<!--end primary content-->
		<?php $theme->display ( 'sidebar' ); ?>
	</div>
	<!--end content-->
	<?php $theme->display ( 'footer' ); ?>