<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include 'header.php'; ?>

			<div id="main-posts">
				<div class="post alt">
					<div class="post-title">
						<h3><?php _e( "Whoops! 404" ); ?></h3>
					</div>
					<div class="post-entry">
						<p><?php _e( "The page you were trying to access is not really there. Please try again." ); ?><p>
					</div>
				</div>
			</div>
		</div>
		<div id="top-secondary">
			<?php include'sidebar.php' ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
<div id="page-bottom">
	<div id="wrapper-bottom">
		<div id="bottom-primary">
			<?php $theme->display_archives() ;?>
			
<?php include 'footer.php'; ?>
