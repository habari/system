<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<ul id="monthly_archives">
	<?php $months = $content->months; foreach( $months as $month ): ?>
		<li>
	<a href="<?php echo $month[ 'url' ]; ?>" title="View entries in <?php
		echo $month[ 'display_month' ] . ", " . $month[ 'year' ];
	?>"><?php
		echo $month[ 'display_month' ] . " " . $month[ 'year' ] . $month[ 'count' ];
	?></a>
		</li>
	<?php endforeach; ?>
</ul>
