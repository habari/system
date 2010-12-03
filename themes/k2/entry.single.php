<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php $theme->display ( 'header'); ?>
<!-- entry.single -->
	<div class="single">
	<div id="primary">
	<div class="navigation">
		<?php if ( $previous = $post->descend() ): ?>
		<div class="left"> &laquo; <a href="<?php echo $previous->permalink ?>" title="<?php echo $previous->slug ?>"><?php echo $previous->title ?></a></div>
		<?php endif; ?>
		<?php if ( $next = $post->ascend() ): ?>
		<div class="right"><a href="<?php echo $next->permalink ?>" title="<?php echo $next->slug ?>"><?php echo $next->title ?></a> &raquo;</div>
		<?php endif; ?>

		<div class="clear"></div>
	</div>

		<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">

		<div class="entry-head">
			<h3 class="entry-title"><a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h3>
			<small class="entry-meta">
			<span class="chronodata"><abbr class="published"><?php $post->pubdate->out(); ?></abbr></span> <?php if ( $show_author ) { _e( 'by %s', array( $post->author->displayname ) );  } ?>
			<?php $theme->comments_link($post); ?>
<?php if ( $loggedin ) { ?>
			<span class="entry-edit"><a href="<?php echo $post->editlink; ?>" title="<?php _e('Edit post'); ?>"><?php _e('Edit'); ?></a></span>
<?php } ?>
<?php if ( count( $post->tags ) > 0 ) { ?>
			<span class="entry-tags"><?php echo $post->tags_out; ?></span>
<?php } ?>
			</small>
		</div>

		<div class="entry-content">
			<?php echo $post->content_out; ?>

		</div>

		</div>
<?php $theme->display ('comments'); ?>
	</div>

	<hr>

	<div class="secondary">

<?php $theme->display ( 'sidebar' ); ?>

	</div>

	<div class="clear"></div>
	</div>
<!-- /entry.single -->
<?php $theme->display ( 'footer' ); ?>
