<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php Plugins::act( 'theme_sidebar_top' ); ?>
	<!--begin secondary content-->
	<div id="secondaryContent" class="span-7 last">
	<h3><a id="rss" href="<?php $theme->feed_alternate(); ?>" class="block"><?php _e('Subscribe to Feed'); ?></a></h3>
	<h2 id="site"><?php _e('Navigation'); ?></h2>
	<ul id="nav">
		<li><a href="<?php Site::out_url( 'habari' ); ?>"><?php _e('Home'); ?></a></li>
		<?php
		// List Pages
		foreach ( $pages as $page ) {
			echo '<li><a href="' . $page->permalink . '" title="' . $page->title . '">' . $page->title . '</a></li>' . "\n";
		}
		?>
	</ul>

	<h2 id="aside"><?php _e('Asides'); ?></h2>
	<ul id="asides">
		<?php
	          foreach($asides as $post):
              echo '<li><span class="date">';
              echo $post->pubdate->out('F j, Y') . ' - ' . '</span>';
              echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>'. $post->content_out;
              echo '</li>';

 		?>
	<?php endforeach; ?>
   </ul>

	<?php $theme->area( 'sidebar' ); ?>
	

	</div>
	<!--end secondary content-->
<?php Plugins::act( 'theme_sidebar_bottom' ); ?>