<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include 'header.php'; ?>

			<div id="main-posts">
				<div class="<?php echo $page_class?>">
				<?php if ( is_array( $post->tags ) && !empty($post->tags) ) : ?>
					<div class="post-tags">
						<?php echo $post->tags_out;?>
					</div>
				<?php endif ?>
					<div class="post-title">
						<h3>
							<a href="<?php echo $post->permalink; ?>" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a>
						</h3>
					</div>
					<div class="post-entry">
						<?php echo $post->content_out; ?>
					</div>
					<div class="post-footer">
					<?php if ( $loggedin ) : ?>
						<span class="post-edit">
						<a href="<?php echo $post->editlink; ?>" title="<?php _e( "Edit post" ); ?>"><?php _e( "Edit" ); ?></a>
						</span>
					<?php endif;?>
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
		
		<?php include 'comments.php'; ?>
		
		<!-- comment form -->
		
		<?php include 'commentform.php'; ?>
		
		<!-- /comment form -->

<?php include 'footer.php'; ?>
