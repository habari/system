<div class="container navigator">
	<span class="older pct10"><a href="#" onclick="timeline.skipLoupeLeft();return false">&laquo; <?php _e('Older'); ?></a></span>
	<span class="currentposition pct15 minor"><?php _e('no results'); ?></span>
	<span class="search pct50">
		<input id="search" type="search" placeholder="<?php _e('Type and wait to search'); ?>" value="<?php echo Utils::htmlspecialchars($search_args); ?>">
	</span>
<?php if( isset( $special_searches ) ) : ?>
	<div class="filters pct15">
		<ul class="dropbutton special_search">	
			<?php foreach ( $special_searches as $term => $text ): ?>
			<li><a href="#<?php echo $term; ?>" title="<?php _e( "Filter results for '%s'" , array( $text ) ); ?>"><?php echo $text; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
	<span class="newer pct10"><a href="#" onclick="timeline.skipLoupeRight();return false"><?php _e('Newer'); ?> &raquo;</a></span>

	<div class="timeline">
		<div class="years">
			<?php $theme->display( 'timeline_items' )?>
		</div>

		<div class="track">
			<div class="handle">
				<span class="resizehandleleft"></span>
				<span class="resizehandleright"></span>
			</div>
		</div>

	</div>

</div>
