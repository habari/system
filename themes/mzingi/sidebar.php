	<!--begin secondary content-->
	<div id="secondaryContent">
		<?php $theme->display ( 'searchform' ); ?>
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
              echo $post->pubdate_out . ' - ' . '</span>';
              echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>'. $post->content_out;
              echo '</li>';

 		?>
	<?php endforeach; ?>
   </ul>

		<h2><?php _e('More Posts'); ?></h2>
		<ul id="moreposts">
			<?php foreach($more_posts as $post): ?>
				<?php
				echo '<li>';
				echo '<a href="' . $post->permalink .'">' . $post->title_out . '</a>';
				echo '</li>';
				?>
			<?php endforeach; ?>					
		</ul>
		
	<h2 id="commentheading"><?php _e('Recent Comments'); ?></h2>
	<ul id="recentcomments">
		
	<?php foreach($recent_comments as $comment): ?>
	<li><a href="<?php echo $comment->url ?>"><?php echo $comment->name ?></a> <?php _e('on'); ?> <a href="<?php echo $comment->post->permalink; ?>"><?php echo $comment->post->title; ?></a></li>
	<?php endforeach; ?>
	</ul>
	<h2 id="loginheading"><?php _e('User Login'); ?></h2>
	<?php $theme->display ( 'loginform' ); ?> 

	</div>
	<!--end secondary content-->