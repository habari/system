<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php foreach ( $years as $year => $year_array ): ?> 
<div class="year">
	<span><?php echo $year; ?></span>
	<div class="months">
		<?php foreach ( $year_array as $pdata ): ?>
		<div><span style="width: <?php echo $pdata->ct; ?>px"><?php _e( date('M', mktime(0, 0, 0, $pdata->month)) ); ?></span></div>
		<?php endforeach; ?>
	</div>
</div>
<?php endforeach; ?>
