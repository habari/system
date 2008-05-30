<?php $theme->display ( 'header' ); ?>
<!--begin content-->
	<div id="content">
		<!--begin primary content-->
		<div id="primaryContent">
			<div class="entry">
						<p><?php _e("It seems you couldn't find what you are looking for."); ?></p><p><?php _e('Perhaps you can try searching.'); ?></p>
						<?php $theme->display ( 'searchform' ); ?>
			</div>
	
					
			</div>
		<!--end primary content-->
		<?php $theme->display ( 'sidebar' ); ?>
	</div>
	<!--end content-->
	<?php $theme->display ( 'footer' ); ?>
