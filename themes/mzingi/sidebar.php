<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php Plugins::act( 'theme_sidebar_top' ); ?>
	<!--begin secondary content-->
	<div id="secondaryContent" class="span-7 last">
	<h3><a id="rss" href="<?php echo $theme->feed_alternate(); ?>" class="block"><?php _e('Subscribe to Feed'); ?></a></h3>
	<h2 id="site"><?php _e('Navigation'); ?></h2>
	<ul id="nav">
		<li><a href="<?php Site::out_url( 'habari' ); ?>"><?php _e('Home'); ?></a></li>
		<?php
		// List Pages
		if( isset( $pages ) && !empty( $pages ) ) {
			foreach ( $pages as $page ) {
				echo '<li><a href="' . $page->permalink . '" title="' . $page->title . '">' . $page->title . '</a></li>' . "\n";
			}
		}
		?>
	</ul>

	<h2 id="aside"><?php _e('Asides'); ?></h2>
	<ul id="asides">
		<?php
			if( isset( $asides ) && !empty( $asides ) ) {
	          foreach($asides as $post):
              echo '<li><span class="date">';
	      // @locale Date formats according to http://php.net/manual/en/function.date.php
              echo $post->pubdate->out( _t( 'F j, Y' ) ) . ' - ' . '</span>';
              echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>'. $post->content_out;
              echo '</li>';
 		?>
	<?php endforeach; } ?>
   </ul>
	<?php echo $theme->area( 'sidebar' ); ?>

	</div>
	<!--end secondary content-->
<?php Plugins::act( 'theme_sidebar_bottom' ); ?>
